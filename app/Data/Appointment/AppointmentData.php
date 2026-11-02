<?php

namespace App\Data\Appointment;

use App\Data\Casts\CarbonCast;
use App\Data\Patient\PatientData;
use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MapName(SnakeCaseMapper::class)]
class AppointmentData extends Data
{
    public function __construct(
        #[FromRouteParameterProperty('appointment')]
        public ?string $uuid = null,
        public ?PatientData $patient,
        #[WithCast(CarbonCast::class)]
        public ?Carbon $date = null,
        public ?PresenceEnum $presence,
        public ?float $value,
        public ?string $notes,
        public ?string $medicalRecord,
    )
    {}

    public static function fromModel(Appointment $appointment): self
    {
        return new self(
            uuid: $appointment->uuid,
            patient: $appointment->relationLoaded('patient')
                ? PatientData::from($appointment->patient->only(['id', 'uuid', 'name', 'time', 'type',]))
                : null,
            date: $appointment->date,
            presence: $appointment->presence,
            value: $appointment->value,
            notes: $appointment->notes,
            medicalRecord: null,
        );
    }

    public static function rules(ValidationContext $context): array
    {
        $dateUnchanged = false;
        $appointmentUuid = $context->payload['uuid'] ?? null;
        $incomingDate = $context->payload['date'] ?? null;

        if (request()->routeIs('appointment.update') && is_string($appointmentUuid) && is_string($incomingDate)) {
            $appointment = Appointment::query()->where('uuid', $appointmentUuid)->first();

            if ($appointment) {
                try {
                    $dateUnchanged = $appointment->date->equalTo(
                        Carbon::parse($incomingDate, config('app.timezone'))
                    );
                } catch (\Exception) {
                    // A regra date rejeita valores invalidos.
                }
            }
        }

        return [
            'patient' => [
                'required',
            ],
            'date' => [
                'required',
                'date',
                Rule::when(!$dateUnchanged, ['after_or_equal:today']),
            ],
            'presence' => [
                'required',
                Rule::enum(PresenceEnum::class)
            ],
            'value' => [
                'nullable',
                'decimal:2',
                'min:0'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
            'medical_record' => [
                'nullable',
                'string',
            ]
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        if (!empty($properties['value']) && !is_string($properties['value'])) {
            $properties['value'] = number_format($properties['value'], 2);
        }

        return $properties;
    }

    public static function messages(...$args): array
    {
        return [
            'patient.required' => 'O paciente é obrigatório.',
            'date.after_or_equal' => 'A data da consulta deve ser hoje ou uma data futura.',
            'presence.required' => 'O tipo de presença é obrigatório.',
            'value.decimal' => 'O valor deve ter duas casas decimais',
            'value.min' => 'O valor deve ser no mínimo 0'
        ];
    }
}
