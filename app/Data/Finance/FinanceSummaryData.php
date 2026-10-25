<?php

namespace App\Data\Finance;

use App\Domains\Finance\Enums\FinancePeriodEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class FinanceSummaryData extends Data
{
    public function __construct(
        public FinancePeriodEnum $period,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public int $appointmentsCount,
        public float $receivableAmount,
        public float $averageTicket,
        public int $patientsCount,
        public int $absencesCount,
        public float $attendanceRate,
    ) {}
}
