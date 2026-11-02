<?php

namespace App\Domains\Appointment\Jobs;

use App\Data\Appointment\AppointmentData;
use App\Domains\Appointment\Repositories\AppointmentRepository;
use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use App\Helpers\DatabaseHelper;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Tests\Domains\Appointment\Jobs\CreateAppointmentJobTest;

/**
 * @see CreateAppointmentJobTest
 */
class CreateAppointmentJob implements ShouldQueue
{
    use Batchable;
    use SerializesModels;

    public function __construct(
        private readonly string $patientTenant,
        private readonly Patient $patient,
        private readonly AppointmentRepository $appointmentRepository,
    ) {
    }

    public function restoreModel(ModelIdentifier $value): Patient
    {
        DatabaseHelper::changeSchema(schema: $this->patientTenant);
        return Patient::query()
            ->select(['id', 'uuid', 'day', 'type', 'value'])
            ->findOrFail($value->id);
    }

    public function handle(): void
    {
        DatabaseHelper::changeSchema(schema: $this->patientTenant);
        $appointment = $this->appointmentRepository->getLastByPatient($this->patient->uuid, checkForCreate: true);

        if (!$appointment) {
            Log::warning('Nenhuma consulta anterior encontrada para o paciente: ' . $this->patient->uuid);
            return;
        }

        $nextDate = $appointment->date
            ->copy()
            ->addDays($this->patient->type->days());

        $targetDayOfWeek = ($this->patient->day + 1) % 7;

        $daysToAdd = ($targetDayOfWeek - $nextDate->dayOfWeek + 7) % 7;

        $nextDate->addDays($daysToAdd);

        $data = AppointmentData::from([
            'patient' => $this->patient,
            'date' => $nextDate,
            'presence' => PresenceEnum::PENDING,
            'value' => $appointment->value ?? $this->patient->value,
        ]);

        $this->appointmentRepository->firstOrCreate([
            'patient_id' => $this->patient->id,
            'date' => $nextDate,
        ], $data);

        Log::info(
            "Consulta do dia {$nextDate->format('d/m/Y H:i:s')} criada com sucesso para o paciente: " . $this->patient->uuid
        );

        DatabaseHelper::changeSchema();
    }
}
