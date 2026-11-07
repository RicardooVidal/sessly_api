<?php

namespace App\Domains\Report\Callbacks;

use App\Domains\Report\Services\ReportService;
use App\Helpers\DatabaseHelper;
use Illuminate\Bus\Batch;
use Throwable;

class FinalizeReportCallback
{
    public function __construct(
        private readonly string $tenant,
        private readonly string $reportUuid,
    ) {}

    public function __invoke(Batch $batch, ?Throwable $e = null): void
    {
        DatabaseHelper::changeSchema(schema: $this->tenant);

        try {
            app(ReportService::class)->finalize(
                tenant: $this->tenant,
                reportUuid: $this->reportUuid,
                batch: $batch,
                exception: $e,
            );
        } finally {
            DatabaseHelper::changeSchema();
        }
    }
}
