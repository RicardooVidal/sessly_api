<?php

namespace App\Domains\Patient\Repositories;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;

class MedicalRecordRepository
{
    public function __construct(
        private readonly MedicalRecord $medicalRecord
    ) {}

    public function findByPatientUuid(string $patientUuid): ?MedicalRecord
    {
        return $this->medicalRecord->query()
            ->whereHas('patient', fn ($query) => $query->where('uuid', $patientUuid))
            ->first();
    }

    public function updatePatientMedicalRecord(
        Patient $patient,
        ?string $record,
        ?string $document,
    ): void {
        $medicalRecord = $this->medicalRecord->query()->where('patient_id', $patient->id)->first();

        if (! $medicalRecord) {
            $medicalRecord = $this->medicalRecord->query()->make([
                'record' => $record,
                'document' => $document,
            ]);
            $medicalRecord->patient()->associate($patient)->save();

            return;
        }

        $medicalRecord->update([
            'record' => $record,
            'document' => $document,
        ]);
    }
}
