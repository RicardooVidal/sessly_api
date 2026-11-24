<?php

namespace App\Domains\Dashboard\Repositories;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Contract\Entities\Contract;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly Patient $patient,
        private readonly Contract $contract,
        private readonly WaitList $waitList,
    ) {}

    public function countAppointments(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->appointment->newQuery()
            ->whereBetween('date', [$startsAt, $endsAt])
            ->count();
    }

    public function countAppointmentsByPresence(
        Carbon $startsAt,
        Carbon $endsAt,
        PresenceEnum $presence
    ): int {
        return $this->appointment->newQuery()
            ->whereBetween('date', [$startsAt, $endsAt])
            ->where('presence', $presence)
            ->count();
    }

    public function countPresentAppointments(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->appointment->newQuery()
            ->whereBetween('date', [$startsAt, $endsAt])
            ->whereIn('presence', PresenceEnum::presentStatuses())
            ->count();
    }

    public function countActivePatients(): int
    {
        return $this->patient->newQuery()
            ->where('active', true)
            ->count();
    }

    public function countNewPatients(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->patient->newQuery()
            ->whereBetween('created_at', [$startsAt, $endsAt])
            ->count();
    }

    public function countContractsExpiring(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->contract->newQuery()
            ->whereBetween('valid_until', [$startsAt, $endsAt])
            ->count();
    }

    public function countWaitList(): int
    {
        return $this->waitList->newQuery()->count();
    }

    public function getNextAppointments(Carbon $startsAt, Carbon $endsAt, int $limit = 5): Collection
    {
        return $this->appointment->newQuery()
            ->with('patient:id,uuid,name')
            ->whereBetween('date', [$startsAt, $endsAt])
            ->where('presence', PresenceEnum::PENDING)
            ->orderBy('date')
            ->limit($limit)
            ->get();
    }
}
