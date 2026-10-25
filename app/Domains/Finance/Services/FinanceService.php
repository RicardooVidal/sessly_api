<?php

namespace App\Domains\Finance\Services;

use App\Data\Finance\FinanceSummaryData;
use App\Data\Finance\IndexFinanceData;
use App\Domains\Finance\Enums\FinancePeriodEnum;
use App\Domains\Finance\Repositories\FinanceRepository;

class FinanceService
{
    public function __construct(
        private readonly FinanceRepository $financeRepository
    ) {}

    public function summary(IndexFinanceData $filters): FinanceSummaryData
    {
        $period = $filters->period ?? FinancePeriodEnum::MONTH;
        $reference = $filters->date ?? now();

        $startsAt = $period->startsAt($reference);
        $endsAt = $period->endsAt($reference);

        $appointmentsCount = $this->financeRepository->countPresent($startsAt, $endsAt);
        $receivableAmount = $this->financeRepository->sumPresentValue($startsAt, $endsAt);
        $patientsCount = $this->financeRepository->countPresentPatients($startsAt, $endsAt);
        $absencesCount = $this->financeRepository->countAbsent($startsAt, $endsAt);
        $attendanceBase = $appointmentsCount + $absencesCount;

        return new FinanceSummaryData(
            period: $period,
            startsAt: $startsAt,
            endsAt: $endsAt,
            appointmentsCount: $appointmentsCount,
            receivableAmount: round($receivableAmount, 2),
            averageTicket: $appointmentsCount > 0
                ? round($receivableAmount / $appointmentsCount, 2)
                : 0.0,
            patientsCount: $patientsCount,
            absencesCount: $absencesCount,
            attendanceRate: $attendanceBase > 0
                ? round(($appointmentsCount / $attendanceBase) * 100, 2)
                : 0.0,
        );
    }
}
