<?php

namespace App\Http\Controllers;

use App\Data\Appointment\AppointmentData;
use App\Data\Appointment\ImportMedicalRecordData;
use App\Data\Appointment\IndexAppointmentData;
use App\Domains\Appointment\Services\AppointmentService;
use App\Domains\Patient\Entities\Patient;
use App\Exceptions\PatientNotActiveException;
use App\Services\PhpWordService;
use Http\Controllers\AppointmentController\IndexTest;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpWord\Exception\Exception;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    /**
     * @see IndexTest
     */
    public function index(IndexAppointmentData $filters): JsonResponse
    {
        $data = $this->appointmentService->getAll($filters);

        return response()->json(['data' => $data]);
    }

    /**
     * @throws PatientNotActiveException
     */
    public function store(AppointmentData $request): AppointmentData
    {
        $patient = Patient::query()->where('uuid', $request->patient->uuid)->first();

        if (!$patient->active) {
            throw new PatientNotActiveException();
        }

        return $this->appointmentService->create($request, $patient)->wrap('data');
    }

    public function show(string $uuid): AppointmentData
    {
        return $this->appointmentService->getByUuid($uuid)->wrap('data');
    }

    public function update(AppointmentData $request): AppointmentData
    {
        $patient = Patient::query()->where('uuid', $request->patient->uuid)->first();

        if (!$patient->active) {
            throw new PatientNotActiveException();
        }

        return $this->appointmentService->update($request, $patient)->wrap('data');
    }

    public function destroy(string $uuid): void
    {
        $this->appointmentService->delete($uuid);
    }
}
