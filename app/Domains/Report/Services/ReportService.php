<?php

namespace App\Domains\Report\Services;

use App\Data\Report\IndexReportData;
use App\Data\Report\ReportData;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Report\Callbacks\FinalizeReportCallback;
use App\Domains\Report\Entities\Report;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Domains\Report\Jobs\GenerateReportChunkJob;
use App\Domains\Report\Repositories\ReportRepository;
use App\Helpers\DatabaseHelper;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

readonly class ReportService
{
    public function __construct(
        private ReportRepository      $reportRepository,
        private ReportWorkbookService $workbookService,
    ) {}

    public function start(IndexReportData $filters, ?int $userId, string $tenant): Report
    {
        $startsAt = $filters->periodStart();
        $endsAt = $filters->periodEnd();
        $referenceMonth = $startsAt->copy();
        $patientId = $filters->patientUuid
            ? Patient::query()->where('uuid', $filters->patientUuid)->value('id')
            : null;

        $patients = Patient::query()
            ->select(['id', 'uuid', 'name'])
            ->when(
                $filters->patientUuid,
                fn ($query, $patientUuid) => $query->where('uuid', $patientUuid)
            )
            ->whereHas(
                'appointments',
                fn ($query) => $query->whereBetween('date', [$startsAt, $endsAt])
            )
            ->orderBy('name')
            ->get();

        $report = $this->reportRepository->create([
            'user_id' => $userId,
            'patient_id' => $patientId,
            'reference_month' => $referenceMonth,
            'period_start' => $startsAt->toDateString(),
            'period_end' => $endsAt->toDateString(),
            'status' => ReportStatusEnum::QUEUED,
            'total_patients' => $patients->count(),
            'processed_patients' => 0,
        ]);

        if ($patients->isEmpty()) {
            $filePath = $this->workbookService->build($tenant, $report, []);

            return $this->reportRepository->update($report, [
                'status' => ReportStatusEnum::COMPLETED,
                'file_path' => $filePath,
                'completed_at' => now(),
            ]);
        }

        $batchSize = max(1, (int) config('reports.batch_size', 50));
        $jobs = [];

        foreach ($patients->chunk($batchSize)->values() as $chunkIndex => $chunk) {
            $jobs[] = new GenerateReportChunkJob(
                tenant: $tenant,
                reportUuid: $report->uuid,
                startDate: $startsAt->toDateString(),
                endDate: $endsAt->toDateString(),
                patientUuids: $chunk->pluck('uuid')->all(),
                chunkIndex: $chunkIndex,
            );
        }

        $batchName = $startsAt->isSameMonth($endsAt)
            ? "Relatório {$startsAt->format('m/Y')}"
            : "Relatório {$startsAt->format('d/m/Y')} a {$endsAt->format('d/m/Y')}";

        DatabaseHelper::changeSchema();

        try {
            $batch = Bus::batch($jobs)
                ->allowFailures()
                ->name($batchName)
                ->finally(new FinalizeReportCallback($tenant, $report->uuid))
                ->dispatch();
        } catch (Throwable $exception) {
            DatabaseHelper::changeSchema(schema: $tenant);

            $this->reportRepository->update($report, [
                'status' => ReportStatusEnum::FAILED,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        } finally {
            DatabaseHelper::changeSchema(schema: $tenant);
        }

        return $this->reportRepository->update($report, [
            'batch_id' => $batch->id,
        ]);
    }

    public function data(Report $report, string $tenant): ReportData
    {
        $downloadUrl = null;

        if (
            $report->status === ReportStatusEnum::COMPLETED
            && $report->file_path
            && Storage::disk('local')->exists($report->file_path)
        ) {
            $downloadUrl = URL::temporarySignedRoute(
                'reports.download',
                now()->addMinutes((int) config('reports.download_ttl_minutes', 30)),
                [
                    'tenant' => $tenant,
                    'report' => $report->uuid,
                ]
            );
        }

        return ReportData::fromReport($report, $downloadUrl);
    }

    public function cancel(string $tenant, string $reportUuid): Report
    {
        $report = $this->reportRepository->findByUuid($reportUuid);

        if ($report->status->isFinished()) {
            return $report;
        }

        if ($report->batch_id) {
            DatabaseHelper::changeSchema();

            try {
                $batch = Bus::findBatch($report->batch_id);

                if ($batch && ! $batch->finished()) {
                    $batch->cancel();
                }
            } finally {
                DatabaseHelper::changeSchema(schema: $tenant);
            }
        }

        $report = $this->reportRepository->update($report, [
            'status' => ReportStatusEnum::CANCELLED,
            'completed_at' => now(),
        ]);

        $this->cleanup($tenant, $report);

        return $report;
    }

    public function finalize(
        string $tenant,
        string $reportUuid,
        Batch $batch,
        ?Throwable $exception = null,
    ): void {
        $report = Report::query()->where('uuid', $reportUuid)->first();

        if (! $report) {
            return;
        }

        if ($report->status === ReportStatusEnum::COMPLETED) {
            $this->cleanFragments($tenant, $reportUuid);

            return;
        }

        if ($report->status === ReportStatusEnum::CANCELLED || $batch->cancelled()) {
            $this->reportRepository->update($report, [
                'status' => ReportStatusEnum::CANCELLED,
                'completed_at' => $report->completed_at ?? now(),
            ]);

            $this->cleanup($tenant, $report);

            return;
        }

        if ($exception || $batch->hasFailures()) {
            $this->fail(
                $tenant,
                $report,
                $exception?->getMessage() ?? 'Um ou mais lotes falharam ao gerar o relatório.'
            );

            return;
        }

        try {
            $patients = $this->readFragments($tenant, $reportUuid);
            $filePath = $this->workbookService->build($tenant, $report, $patients);

            $this->reportRepository->update($report, [
                'status' => ReportStatusEnum::COMPLETED,
                'file_path' => $filePath,
                'processed_patients' => $report->total_patients,
                'error_message' => null,
                'completed_at' => now(),
            ]);

            $this->cleanFragments($tenant, $reportUuid);
        } catch (Throwable $exception) {
            Log::error('Falha ao finalizar relatório', [
                'report' => $reportUuid,
                'tenant' => $tenant,
                'exception' => $exception,
            ]);

            $this->fail($tenant, $report, $exception->getMessage());
        }
    }

    private function fail(string $tenant, Report $report, string $message): void
    {
        $report = $this->reportRepository->update($report, [
            'status' => ReportStatusEnum::FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ]);

        $this->cleanup($tenant, $report);
    }

    private function cleanup(string $tenant, Report $report): void
    {
        if ($report->file_path) {
            Storage::disk('local')->delete($report->file_path);
        }

        $this->cleanFragments($tenant, $report->uuid);
    }

    private function cleanFragments(string $tenant, string $reportUuid): void
    {
        Storage::disk('local')->deleteDirectory(
            sprintf('reports/%s/%s', $tenant, $reportUuid)
        );
    }

    /**
     * @return array<int, array{patient_uuid: string, patient_name: string, appointments: array<int, array{date: string, effective_value: float, presence: string, notes: ?string}>}>
     */
    private function readFragments(string $tenant, string $reportUuid): array
    {
        $directory = sprintf('reports/%s/%s', $tenant, $reportUuid);
        $files = Storage::disk('local')->files($directory);

        sort($files, SORT_NATURAL);

        $patients = [];

        foreach ($files as $file) {
            $fragment = json_decode(
                Storage::disk('local')->get($file),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (is_array($fragment)) {
                array_push($patients, ...$fragment);
            }
        }

        return $patients;
    }
}
