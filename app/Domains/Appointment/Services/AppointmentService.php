<?php

namespace App\Domains\Appointment\Services;

use App\Data\Appointment\AppointmentData;
use App\Data\Appointment\IndexAppointmentData;
use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Repositories\AppointmentRepository;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Services\MedicalRecordService;
use App\Models\GoogleAccount;
use App\Services\GoogleCalendarService;
use Google\Service\Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly GoogleCalendarService $googleCalendarService,
        private readonly MedicalRecordService $medicalRecordService,
    ) {}

    public function getAll(IndexAppointmentData $filters): array
    {
        return $this->appointmentRepository->getAll($filters)->toArray();
    }

    public function getByUuid(string $uuid): AppointmentData
    {
        return AppointmentData::from($this->appointmentRepository->getByUuid($uuid));
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function create(AppointmentData $appointmentData, Patient $patient): AppointmentData
    {
        $appointment = $this->appointmentRepository->create($appointmentData->except('medicalRecord'));
        $appointment->patient()->associate($patient);
        $appointment->save();

        $googleAccount = $this->getGoogleAccount();

        if ($googleAccount) {
            $event = $this->googleCalendarService->createMeeting(
                googleAccount: $googleAccount,
                name: "{$appointment->patient->name} - {$appointment->date->format('d/m/Y H:i')}",
                start: $appointment->date,
                end: $appointment->date->addMinutes($appointment->patient->time->value),
            );

            $appointment->update([
                'google_event_id' => $event->getId(),
                'google_meet_url' => $event->getHangoutLink(),
            ]);
        }

        $this->checkMedicalRecord($appointmentData, $appointment);

        return AppointmentData::from($appointment);
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function update(AppointmentData $appointmentData, Patient $patient): AppointmentData
    {
        $this->appointmentRepository->update($appointmentData->except('medicalRecord'));
        $appointment = Appointment::query()->where('uuid', $appointmentData->uuid)->firstOrFail();
        $appointment->patient()->associate($patient);
        $appointment->save();

        $googleAccount = $this->getGoogleAccount();

        if ($googleAccount && $appointment->google_event_id) {
            $event = $this->googleCalendarService->updateMeeting(
                eventId: $appointment->google_event_id,
                googleAccount: $googleAccount,
                name: "{$appointment->patient->name} - {$appointment->date->format('d/m/Y H:i')}",
                start: $appointment->date,
                end: $appointment->date->addMinutes($appointment->patient->time->value),
            );

            $appointment->update([
                'google_event_id' => $event->getId(),
                'google_meet_url' => $event->getHangoutLink(),
            ]);
        }

        $this->checkMedicalRecord($appointmentData, $appointment);

        return AppointmentData::from($appointment);
    }

    /**
     * @throws Exception
     */
    public function delete(string $uuid): void
    {
        $appointment = $this->appointmentRepository->getByUuid($uuid);

        $googleAccount = $this->getGoogleAccount();

        if ($googleAccount && $appointment->google_event_id) {
            $this->googleCalendarService->deleteMeeting(
                eventId: $appointment->google_event_id,
                googleAccount: $googleAccount,
            );
        }

        $this->appointmentRepository->delete($uuid);
    }

    public static function getGoogleAccount(): ?GoogleAccount
    {
        try {
            return GoogleAccount::query()->where(
                'user_id',
                auth()->id()
            )->firstOrFail();
        } catch (ModelNotFoundException) {
            Log::error('Conta do google para o usuário ' . auth()->id . ' não encontrado!');
            return null;
        }
    }

    /**
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    private function checkMedicalRecord(AppointmentData $appointmentData): void
    {
        if ($appointmentData->medicalRecord) {
            $this->medicalRecordService->updatePatientMedicalRecord(
                $appointmentData->patient->uuid,
                $appointmentData->medicalRecord
            );
        }
    }
}
