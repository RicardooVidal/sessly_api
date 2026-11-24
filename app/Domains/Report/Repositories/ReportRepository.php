<?php

namespace App\Domains\Report\Repositories;

use App\Domains\Report\Entities\Report;

readonly class ReportRepository
{
    public function __construct(
        private Report $report
    ) {}

    public function create(array $data): Report
    {
        return $this->report->newQuery()->create($data);
    }

    public function findByUuid(string $uuid): Report
    {
        return $this->report->newQuery()->where('uuid', $uuid)->firstOrFail();
    }

    public function update(Report $report, array $data): Report
    {
        $report->update($data);

        return $report->refresh();
    }

    public function incrementProcessed(Report $report, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        Report::query()
            ->whereKey($report->getKey())
            ->increment('processed_patients', $amount);
    }
}
