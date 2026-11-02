<?php

namespace App\Data\Dashboard;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class DashboardSummaryData extends Data
{
    public function __construct(
        public Carbon $generatedAt,
        public DashboardPeriodData $today,
        public DashboardPeriodData $week,
        public DashboardPatientsData $patients,
        public DashboardContractsData $contracts,
        public DashboardWaitListData $waitList,
        public DataCollection $nextAppointments,
    ) {}
}
