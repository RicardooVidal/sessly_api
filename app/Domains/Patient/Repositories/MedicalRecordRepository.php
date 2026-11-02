<?php

namespace App\Domains\Patient\Repositories;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;

class MedicalRecordRepository
{
    public function __construct(
        private readonly MedicalRecord $medicalRecord
    ) {}

    public function updatePatientMedicalRecord(Patient $patient, string $filePath): void
    {
        $medicalRecord = $this->medicalRecord->query()->where('patient_id', $patient->id)->first();

        if (!$medicalRecord) {
            $medicalRecord = $this->medicalRecord->query()->make(['document' => $filePath]);
            $medicalRecord->patient()->associate($patient)->save();
            return;
        }

        $medicalRecord->update(['document' => $filePath]);
    }
}
