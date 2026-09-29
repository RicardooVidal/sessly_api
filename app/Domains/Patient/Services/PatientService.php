<?php

namespace App\Domains\Patient\Services;

use App\Data\Patient\IndexPatientData;
use App\Data\Patient\PatientData;
use App\Domains\Patient\Repositories\PatientRepository;

class PatientService
{
    public function __construct(
        private readonly PatientRepository $patientRepository,
    ) {}

    public function getAll(IndexPatientData $filters): array
    {
        return $this->patientRepository->getAll($filters)->toArray();
    }

    public function select(IndexPatientData $filters): array
    {
        return $this->patientRepository->select($filters)->toArray();
    }

    public function getByUuid(string $uuid): PatientData
    {
        return PatientData::from($this->patientRepository->getByUuid($uuid)?->toArray());
    }

    public function create(PatientData $patientData): PatientData
    {
        return PatientData::from($this->patientRepository
            ->create($patientData)
            ->toArray()
        );
    }

    public function update(PatientData $patientData): bool
    {
        return $this->patientRepository->update($patientData);
    }

    public function delete(string $uuid): void
    {
        $this->patientRepository->delete($uuid);
    }

    public function importMedicalRecord(string $patientUuid, string $filePath): void
    {
        $this->patientRepository->updateOrCreateMedicalRecord($patientUuid, $filePath);
    }
}
