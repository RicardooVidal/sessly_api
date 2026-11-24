<?php

namespace App\Data\Report;

use App\Domains\Report\Entities\Report;
use App\Domains\Report\Enums\ReportStatusEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ReportData extends Data
{
    public function __construct(
        public string $uuid,
        public string $referenceMonth,
        public string $periodStart,
        public string $periodEnd,
        public string $periodLabel,
        public string $status,
        public string $statusDescription,
        public int $progress,
        public int $totalPatients,
        public int $processedPatients,
        public ?string $downloadUrl,
        public ?string $errorMessage,
        public ?string $completedAt,
    ) {}

    public static function fromReport(Report $report, ?string $downloadUrl = null): self
    {
        $progress = $report->status === ReportStatusEnum::COMPLETED
            ? 100
            : ($report->total_patients > 0
                ? (int) round(($report->processed_patients / $report->total_patients) * 100)
                : 0);

        return new self(
            uuid: $report->uuid,
            referenceMonth: $report->reference_month->format('Y-m'),
            periodStart: $report->periodStartDate()->toDateString(),
            periodEnd: $report->periodEndDate()->toDateString(),
            periodLabel: $report->periodLabel(),
            status: $report->status->value,
            statusDescription: $report->status->description(),
            progress: min(100, max(0, $progress)),
            totalPatients: $report->total_patients,
            processedPatients: $report->processed_patients,
            downloadUrl: $downloadUrl,
            errorMessage: $report->error_message,
            completedAt: $report->completed_at?->toIso8601String(),
        );
    }
}
