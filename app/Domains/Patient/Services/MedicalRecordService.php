<?php

namespace App\Domains\Patient\Services;

use App\Domains\Patient\Repositories\MedicalRecordRepository;
use App\Domains\Patient\Repositories\PatientRepository;
use App\Services\PhpWordService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception;

readonly class MedicalRecordService
{
    public function __construct(
        private PhpWordService $phpWordService,
        private MedicalRecordRepository $medicalRecordRepository,
        private PatientRepository $patientRepository,
    ) {}

    /**
     * @throws Exception
     */
    public function getPatientMedicalRecordEncoded(string $patientUuid): ?string
    {
        $filePath = 'medical_records/' . $patientUuid . '.html';

        if (!Storage::exists($filePath)) {
            return null;
        }

        $html = Storage::get($filePath);

        return base64_encode($html);
    }

    /**
     * @throws Exception
     */
    public function updatePatientMedicalRecord(string $patientUuid, string $medicalRecord): void
    {
        $filePath = $this->phpWordService->convertToDocx($patientUuid, $medicalRecord);
        $patient = $this->patientRepository->getByUuid($patientUuid);
        $this->medicalRecordRepository->updatePatientMedicalRecord($patient, $filePath);
    }
}
