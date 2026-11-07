<?php

namespace App\Domains\Report\Jobs;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Report\Entities\Report;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Domains\Report\Repositories\ReportRepository;
use App\Helpers\DatabaseHelper;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateReportChunkJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $patientUuids
     */
    public function __construct(
        private readonly string $tenant,
        private readonly string $reportUuid,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $patientUuids,
        private readonly int $chunkIndex,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        DatabaseHelper::changeSchema(schema: $this->tenant);

        try {
            $report = Report::query()->where('uuid', $this->reportUuid)->first();

            if (! $report || $report->status->isFinished()) {
                return;
            }

            if ($report->status === ReportStatusEnum::QUEUED) {
                $report->update(['status' => ReportStatusEnum::PROCESSING]);
            }

            $fragmentPath = $this->fragmentPath($this->reportUuid, $this->chunkIndex);

            if (Storage::disk('local')->exists($fragmentPath)) {
                return;
            }

            $blocks = $this->buildBlocks($this->patientUuids);

            Storage::disk('local')->makeDirectory(dirname($fragmentPath));
            Storage::disk('local')->put(
                $fragmentPath,
                json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );

            app(ReportRepository::class)->incrementProcessed($report, count($this->patientUuids));
        } finally {
            DatabaseHelper::changeSchema();
        }
    }

    /**
     * @param  array<int, string>  $patientUuids
     * @return array<int, array{patient_uuid: string, patient_name: string, appointments: array<int, array{date: string, effective_value: float, presence: string, notes: ?string}>}>
     */
    private function buildBlocks(array $patientUuids): array
    {
        $startsAt = Carbon::parse($this->startDate)->startOfDay();
        $endsAt = Carbon::parse($this->endDate)->endOfDay();

        $patients = Patient::query()
            ->whereIn('uuid', $patientUuids)
            ->get(['id', 'uuid', 'name', 'value'])
            ->keyBy('uuid');

        $appointments = Appointment::query()
            ->whereIn('patient_id', $patients->pluck('id'))
            ->whereBetween('date', [$startsAt, $endsAt])
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->groupBy('patient_id');

        $blocks = [];

        foreach ($patientUuids as $patientUuid) {
            /** @var Patient $patient */
            $patient = $patients->get($patientUuid);

            if (! $patient) {
                continue;
            }

            $patientAppointments = $appointments->get($patient->id, collect());

            $blocks[] = [
                'patient_uuid' => $patient->uuid,
                'patient_name' => $patient->name,
                'appointments' => $patientAppointments->map(fn (Appointment $appointment) => [
                    'date' => $appointment->date->toDateString(),
                    'effective_value' => (float) ($appointment->value ?? $patient->value ?? 0),
                    'presence' => $appointment->presence->value,
                    'notes' => $appointment->notes,
                ])->values()->all(),
            ];
        }

        return $blocks;
    }

    private function fragmentPath(string $reportUuid, int $chunkIndex): string
    {
        return sprintf(
            'reports/%s/%s/chunk-%05d.json',
            $this->tenant,
            $reportUuid,
            $chunkIndex
        );
    }
}
