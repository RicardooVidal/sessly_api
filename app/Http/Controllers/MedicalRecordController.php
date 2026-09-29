<?php

namespace App\Http\Controllers;

use App\Data\Appointment\AppointmentData;
use App\Data\Appointment\ImportMedicalRecordData;
use App\Data\Appointment\IndexAppointmentData;
use App\Domains\Appointment\Services\AppointmentService;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Services\MedicalRecordService;
use App\Exceptions\PatientNotActiveException;
use App\Services\PhpWordService;
use Http\Controllers\AppointmentController\IndexTest;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpWord\Exception\Exception;

class MedicalRecordController extends Controller
{
    public function __construct(
        private readonly MedicalRecordService $medicalRecordService,
        private readonly PhpWordService $phpWordService,
    ) {}

    /**
     * @throws Exception
     */
    public function importMedicalRecord(ImportMedicalRecordData $request): JsonResponse
    {
        $content = $this->phpWordService
            ->setFilePathByUpload($request->file)
            ->convertToHtml()
            ->extractBody()
            ->getContent();

        return response()->json([
            'content' => $content,
        ]);
    }

    /**
     * @throws Exception
     */
    public function getMedicalRecord(string $patientUuid): JsonResponse
    {
        return response()->json([
            'content' => $this->medicalRecordService->getPatientMedicalRecordEncoded($patientUuid)
        ]);
    }
}
