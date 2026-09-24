<?php

namespace App\Domains\Appointment\Repositories;

use App\Data\Appointment\AppointmentData;
use App\Data\Appointment\IndexAppointmentData;
use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Entities\Contract;
use App\Domains\Patient\Entities\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use staabm\SideEffectsDetector\SideEffect;

class AppointmentRepository
{
    public function __construct(
        private readonly Appointment $appointment
    ) {}

    public function getByUuid(string $uuid): Appointment
    {
        return $this->appointment
            ->with(['patient:id,uuid,name,time,type'])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function getAll(IndexAppointmentData $filters): Collection
    {
        return $this->appointment
            ->with(['patient:id,uuid,name,type,time,document'])
            ->when($filters->uuid, fn($query, $uuid) => $query->where('uuid', $uuid))
            ->when(
                $filters->date,
                fn($query, $date) => $query->whereBetween('date', [
                    $date->startOfDay()->toDateTimeString(),
                    $date->endOfDay()->toDateTimeString(),
                ]))
            ->when($filters->type, fn($query, $type) => $query->where('type', $type->value))
            ->when($filters->time, fn($query, $time) => $query->where('time', $time->value))
            ->when(
                $filters->patientUuid,
                fn(Builder $query, $patientUuid) =>
                $query->whereHas('patient', fn ($q) => $q->where('uuid', $patientUuid))
            )
            ->get()
            ->makeHidden('patient_id');
    }

    public function create(AppointmentData $appointmentData): Appointment
    {
        $appointment = $this->appointment->make($appointmentData->toArray());

        $patient = Patient::query()
            ->where('uuid', $appointmentData->patient->uuid)
            ->firstOrFail();

        $appointment->patient()->associate($patient);
        $appointment->save();

        return $appointment;
    }

    public function update(AppointmentData $appointmentData): bool
    {
        return $this->appointment
            ->where('uuid', $appointmentData->uuid)
            ->update($appointmentData->except('appointment','patient')->toArray());
    }

    public function delete(string $uuid): void
    {
        $appointment = $this->appointment->where('uuid', $uuid)->firstOrFail();

        $appointment->delete();
    }
}
