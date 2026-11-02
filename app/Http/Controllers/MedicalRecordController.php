<?php

namespace App\Http\Controllers;

use App\Data\Appointment\ImportMedicalRecordData;
use App\Domains\Patient\Services\MedicalRecordService;
use App\Services\PhpWordService;
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
            'data' => [
                'content' => $content,
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function getMedicalRecord(string $patientUuid): JsonResponse
    {
        return response()->json([
            'data' => [
                'content' => $this->medicalRecordService->getPatientMedicalRecordEncoded($patientUuid),
            ],
        ]);
    }
}
