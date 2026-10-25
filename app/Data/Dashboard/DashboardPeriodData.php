<?php

namespace App\Data\Dashboard;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class DashboardPeriodData extends Data
{
    public function __construct(
        public Carbon $startsAt,
        public Carbon $endsAt,
        public int $appointmentsCount,
        public int $presentCount,
        public int $pendingCount,
        public int $absentCount,
        public float $attendanceRate,
    ) {}
}
