<?php

namespace App\Domains\Patient\Services;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Repositories\MedicalRecordRepository;
use App\Domains\Patient\Repositories\PatientRepository;
use App\Services\PhpWordService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
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
        $medicalRecord = $this->medicalRecordRepository->findByPatientUuid($patientUuid);
        $encryptedRecord = $medicalRecord?->getRawOriginal('record');

        if ($encryptedRecord !== null && $encryptedRecord !== '') {
            try {
                $html = $medicalRecord->record;
            } catch (DecryptException) {
                $html = $encryptedRecord;
            }

            return base64_encode($html);
        }

        $filePath = 'medical_records/'.$patientUuid.'.html';

        if (! Storage::exists($filePath)) {
            return null;
        }

        $html = Storage::get($filePath);

        return base64_encode($html);
    }

    /**
     * @throws Exception
     */
    public function updatePatientMedicalRecord(
        string $patientUuid,
        string $medicalRecord,
        bool $deleteLegacyHtml = true,
    ): void {
        $patient = $this->patientRepository->getByUuid($patientUuid);
        $this->updateMedicalRecord($patient, $medicalRecord, $deleteLegacyHtml);
    }

    /**
     * @throws Exception
     */
    public function updateMedicalRecord(
        Patient $patient,
        string $medicalRecord,
        bool $deleteLegacyHtml = true,
    ): void {
        $html = base64_decode($medicalRecord, true);

        if ($html === false) {
            throw new InvalidArgumentException('Prontuário inválido.');
        }

        $html = $this->phpWordService->normalizeHtmlString($html);

        $htmlPath = "medical_records/{$patient->uuid}.html";

        if ($this->isBlankHtml($html)) {
            Storage::delete("medical_records/{$patient->uuid}.docx");

            if ($deleteLegacyHtml) {
                Storage::delete($htmlPath);
            }
            $this->medicalRecordRepository->updatePatientMedicalRecord($patient, null, null);

            return;
        }

        $filePath = $this->phpWordService->convertHtmlToDocx($patient->uuid, $html);
        $this->medicalRecordRepository->updatePatientMedicalRecord($patient, $html, $filePath);

        if ($deleteLegacyHtml) {
            Storage::delete($htmlPath);
        }
    }

    private function isBlankHtml(string $html): bool
    {
        return trim(strip_tags($html)) === '';
    }
}
