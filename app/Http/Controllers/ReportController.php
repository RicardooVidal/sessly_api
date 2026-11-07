<?php

namespace App\Http\Controllers;

use App\Data\Report\IndexReportData;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Domains\Report\Repositories\ReportRepository;
use App\Domains\Report\Services\ReportService;
use App\Helpers\DatabaseHelper;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportRepository $reportRepository,
    ) {}

    public function store(IndexReportData $data, Request $request): JsonResponse
    {
        $tenant = $this->tenantSchema($request);
        $report = $this->reportService->start($data, auth()->id(), $tenant);

        return response()->json([
            'data' => $this->reportService->data($report, $tenant)->toArray(),
        ], 202);
    }

    public function show(string $report, Request $request): JsonResponse
    {
        $tenant = $this->tenantSchema($request);
        $report = $this->reportRepository->findByUuid($report);

        return response()->json([
            'data' => $this->reportService->data($report, $tenant)->toArray(),
        ]);
    }

    public function cancel(string $report, Request $request): JsonResponse
    {
        $tenant = $this->tenantSchema($request);
        $report = $this->reportService->cancel($tenant, $report);

        return response()->json([
            'data' => $this->reportService->data($report, $tenant)->toArray(),
        ]);
    }

    public function download(string $tenant, string $report): StreamedResponse
    {
        DatabaseHelper::changeSchema(schema: $tenant);

        try {
            $report = $this->reportRepository->findByUuid($report);

            abort_unless(
                $report->status === ReportStatusEnum::COMPLETED && $report->file_path,
                404
            );

            abort_unless(Storage::disk('local')->exists($report->file_path), 404);

            $start = $report->periodStartDate();
            $end = $report->periodEndDate();

            $filename = $start->isSameMonth($end)
                ? sprintf('relatorio-%s.xlsx', $start->format('Y-m'))
                : sprintf('relatorio-%s_a_%s.xlsx', $start->format('Y-m-d'), $end->format('Y-m-d'));

            return Storage::disk('local')->download(
                $report->file_path,
                $filename
            );
        } finally {
            DatabaseHelper::changeSchema();
        }
    }

    private function tenantSchema(Request $request): string
    {
        $companyUuid = $request->header('X-Tenant');

        DatabaseHelper::changeSchema();

        try {
            $tenant = Company::query()
                ->where('uuid', $companyUuid)
                ->value('company');
        } finally {
            DatabaseHelper::changeSchema($companyUuid);
        }

        abort_unless($tenant, 400, 'Tenant inválido.');

        return $tenant;
    }
}
