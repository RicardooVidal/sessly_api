<?php

namespace App\Data\Patient;

use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Enums\GenderEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MapName(SnakeCaseMapper::class)]
class PatientData extends Data
{
    public function __construct(
        #[FromRouteParameterProperty('patient')]
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $document = null,
        public ?string $phone = null,
        public ?GenderEnum $gender = null,
        public ?int $day = null,
        public ?string $email = null,
        public ?bool $active = null,
        public ?bool $create_appointments = null,
        public ?bool $notify_appointment = null,
        public ?string $notes = null,
        public ?float $value = null,
        public ?AppointmentTypeEnum $type = null,
        public ?AppointmentTimeEnum $time = null,
    )
    {
    }

    public static function rules(ValidationContext $context): array
    {
        if (!$context->path->isRoot()) {
            return [
                'uuid' => [
                    'required',
                    'uuid',
                    Rule::exists(Patient::class, 'uuid')
                        ->withoutTrashed(),
                ]
            ];
        }

        $uuid = data_get($context->payload, 'uuid');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'document' => [
                'required',
                'string',
                'min:11',
                'max:14',
                Rule::unique('patients', 'document')
                    ->withoutTrashed()
                    ->ignore($uuid, 'uuid')
            ],
            'phone' => ['required', 'string', 'min:10', 'max:15'],
            'gender' => ['required', Rule::enum(GenderEnum::class)],
            'email' => ['required', 'email', 'max:100'],
            'active' => ['required', 'boolean'],
            'create_appointments' => ['required', 'boolean'],
            'notify_appointment' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(AppointmentTypeEnum::class)],
            'time' => ['required', Rule::enum(AppointmentTimeEnum::class)],
            'value' => ['nullable', 'numeric']
        ];
    }
}
