<?php

namespace App\Http\Controllers;

use App\Data\Patient\IndexPatientData;
use App\Data\Patient\PatientData;
use App\Data\SelectData;
use App\Data\SelectDataCollection;
use App\Domains\Patient\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Tests\Http\Controllers\PatientController\IndexTest;
use Tests\Http\Controllers\PatientController\GetOptionsForAppointmentTest;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService
    ) {}

    /**
     * @see IndexTest
     */
    public function index(IndexPatientData $request): JsonResponse
    {
        $data = $this->patientService->getAll($request);

        return response()->json(['data' => $data]);
    }

    public function select(IndexPatientData $request): SelectDataCollection
    {
        $data = $this->patientService->select($request);

        return (new SelectDataCollection(SelectData::class, $data))->wrap('data');
    }

   public function store(PatientData $request): PatientData
    {
        return $this->patientService->create($request)->wrap('data');
    }

    public function show(string $uuid): PatientData
    {
        return $this->patientService->getByUuid($uuid)->wrap('data');
    }

    public function update(PatientData $request): PatientData
    {
        $this->patientService->update($request);

        return $this->show($request->uuid);
    }

    public function destroy(string $uuid): void
    {
        $this->patientService->delete($uuid);
    }

    /**
     * @see GetOptionsForAppointmentTest
     */
    public function getOptionsForAppointment(string $uuid): PatientData
    {
        return $this->patientService->getOptionsForAppointment($uuid)->wrap('data');
    }
}
