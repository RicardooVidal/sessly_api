<?php

namespace App\Domains\Patient\Repositories;

use App\Data\Patient\IndexPatientData;
use App\Data\Patient\PatientData;
use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;
use App\Helpers\DocumentHelper;
use App\Scopes\PatientScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use MongoDB\BSON\Document;

class PatientRepository
{
    public function __construct(
        private readonly Patient $patient
    ) {}

    public function getAll(IndexPatientData $filters, bool $paginated = true): Collection|LengthAwarePaginator
    {
        /** @var Collection|LengthAwarePaginator */
        return $this->patient
            ->withGlobalScope('patients', new PatientScope($filters))
            ->get();
    }

    public function select(IndexPatientData $filters): Collection
    {
        /** @var Collection|LengthAwarePaginator */
        return $this->patient
            ->withGlobalScope('patients', new PatientScope($filters))
            ->select(['uuid', 'name'])
            ->get();
    }

    public function getByUuid(string $uuid): Patient
    {
        return $this->patient->where('uuid', $uuid)->firstOrFail();
    }

    public function create(PatientData $patientData): Patient
    {
        return $this->patient->create($patientData->except('uuid')->toArray());
    }

    public function update(PatientData $patientData): bool
    {
        return $this->patient
            ->where('uuid', $patientData->uuid)
            ->update($patientData->except('uuid')->toArray());
    }

    public function delete(string $uuid): void
    {
        $this->patient->where('uuid', $uuid)->firstOrFail()?->delete();
    }

    public function getOptionsForAppointment(string $uuid): ?Patient
    {
        return $this->patient->query()
            ->where('uuid', $uuid)
            ->select([
                'uuid',
                'day',
                'value'
            ])->firstOrFail();
    }
}
