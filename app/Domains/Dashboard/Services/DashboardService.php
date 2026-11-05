<?php

namespace App\Domains\Dashboard\Services;

use App\Data\Dashboard\DashboardContractsData;
use App\Data\Dashboard\DashboardNextAppointmentData;
use App\Data\Dashboard\DashboardPatientsData;
use App\Data\Dashboard\DashboardPeriodData;
use App\Data\Dashboard\DashboardSummaryData;
use App\Data\Dashboard\DashboardWaitListData;
use App\Data\SelectData;
use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Dashboard\Repositories\DashboardRepository;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Spatie\LaravelData\DataCollection;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository
    ) {}

    public function summary(): DashboardSummaryData
    {
        $now = now();

        $todayStartsAt = $now->copy()->startOfDay();
        $todayEndsAt = $now->copy()->endOfDay();
        $weekStartsAt = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEndsAt = $now->copy()->startOfWeek(Carbon::MONDAY)->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $monthStartsAt = $now->copy()->startOfMonth()->startOfDay();
        $monthEndsAt = $now->copy()->endOfMonth()->endOfDay();
        $contractsEndsAt = $now->copy()->addDays(30)->endOfDay();

        $nextAppointments = $this->dashboardRepository
            ->getNextAppointments($now->copy(), $todayEndsAt)
            ->map(fn (Appointment $appointment) => new DashboardNextAppointmentData(
                uuid: $appointment->uuid,
                date: $appointment->date,
                presence: $appointment->presence,
                patient: new SelectData(
                    uuid: $appointment->patient->uuid,
                    name: $appointment->patient->name,
                ),
            ));

        return new DashboardSummaryData(
            generatedAt: $now,
            today: $this->periodSummary($todayStartsAt, $todayEndsAt),
            week: $this->periodSummary($weekStartsAt, $weekEndsAt),
            patients: new DashboardPatientsData(
                activeCount: $this->dashboardRepository->countActivePatients(),
                newThisMonth: $this->dashboardRepository->countNewPatients(
                    $monthStartsAt,
                    $monthEndsAt,
                ),
            ),
            contracts: new DashboardContractsData(
                expiringInThirtyDays: $this->dashboardRepository->countContractsExpiring(
                    $todayStartsAt,
                    $contractsEndsAt,
                ),
            ),
            waitList: new DashboardWaitListData(
                count: $this->dashboardRepository->countWaitList(),
            ),
            nextAppointments: new DataCollection(
                DashboardNextAppointmentData::class,
                $nextAppointments,
            ),
        );
    }

    private function periodSummary(Carbon $startsAt, Carbon $endsAt): DashboardPeriodData
    {
        $presentCount = $this->dashboardRepository->countPresentAppointments(
            $startsAt,
            $endsAt,
        );
        $pendingCount = $this->dashboardRepository->countAppointmentsByPresence(
            $startsAt,
            $endsAt,
            PresenceEnum::PENDING,
        );
        $absentCount = $this->dashboardRepository->countAppointmentsByPresence(
            $startsAt,
            $endsAt,
            PresenceEnum::ABSENT,
        );

        return new DashboardPeriodData(
            startsAt: $startsAt,
            endsAt: $endsAt,
            appointmentsCount: $this->dashboardRepository->countAppointments(
                $startsAt,
                $endsAt,
            ),
            presentCount: $presentCount,
            pendingCount: $pendingCount,
            absentCount: $absentCount,
            attendanceRate: $this->attendanceRate($presentCount, $absentCount),
        );
    }

    private function attendanceRate(int $presentCount, int $absentCount): float
    {
        $evaluatedCount = $presentCount + $absentCount;

        if ($evaluatedCount === 0) {
            return 0.0;
        }

        return round(($presentCount / $evaluatedCount) * 100, 2);
    }
}
