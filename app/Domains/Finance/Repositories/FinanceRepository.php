<?php

namespace App\Domains\Finance\Repositories;

use App\Domains\Appointment\Entities\Appointment;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinanceRepository
{
    public function __construct(
        private readonly Appointment $appointment
    ) {}

    public function countPresent(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->queryForPeriod($startsAt, $endsAt)
            ->whereIn('appointments.presence', PresenceEnum::presentStatuses())
            ->count('appointments.id');
    }

    public function countAbsent(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->queryForPeriod($startsAt, $endsAt)
            ->where('appointments.presence', PresenceEnum::ABSENT)
            ->count('appointments.id');
    }

    public function countPresentPatients(Carbon $startsAt, Carbon $endsAt): int
    {
        return $this->queryForPeriod($startsAt, $endsAt)
            ->whereIn('appointments.presence', PresenceEnum::presentStatuses())
            ->distinct()
            ->count('appointments.patient_id');
    }

    public function sumPaidValue(Carbon $startsAt, Carbon $endsAt): float
    {
        return (float) $this->queryForPeriod($startsAt, $endsAt)
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->where('appointments.presence', PresenceEnum::PRESENT)
            ->sum(DB::raw('COALESCE(appointments.value, patients.value, 0)'));
    }

    public function sumPresentToReceiveValue(Carbon $startsAt, Carbon $endsAt): float
    {
        return (float) $this->queryForPeriod($startsAt, $endsAt)
            ->join('patients', 'patients.id', '=', 'appointments.patient_id')
            ->where('appointments.presence', PresenceEnum::PRESENT_TO_RECEIVE)
            ->sum(DB::raw('COALESCE(appointments.value, patients.value, 0)'));
    }

    private function queryForPeriod(Carbon $startsAt, Carbon $endsAt): Builder
    {
        return $this->appointment->newQuery()
            ->whereBetween('appointments.date', [$startsAt, $endsAt]);
    }
}
