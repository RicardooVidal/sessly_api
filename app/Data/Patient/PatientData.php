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
            'day' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
            'create_appointments' => ['required', 'boolean'],
            'notify_appointment' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(AppointmentTypeEnum::class)],
            'time' => ['required', Rule::enum(AppointmentTimeEnum::class)],
            'value' => ['nullable', 'numeric']
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'uuid.required' => 'O identificador do paciente é obrigatório.',
            'uuid.uuid' => 'O identificador do paciente deve ser um UUID válido.',
            'uuid.exists' => 'O paciente informado não foi encontrado.',
            'name.required' => 'O nome do paciente é obrigatório.',
            'name.string' => 'O nome do paciente deve ser um texto.',
            'name.min' => 'O nome do paciente deve ter pelo menos :min caracteres.',
            'name.max' => 'O nome do paciente deve ter no máximo :max caracteres.',
            'document.required' => 'O documento do paciente é obrigatório.',
            'document.string' => 'O documento do paciente deve ser um texto.',
            'document.min' => 'O documento do paciente deve ter pelo menos :min caracteres.',
            'document.max' => 'O documento do paciente deve ter no máximo :max caracteres.',
            'document.unique' => 'Já existe um paciente cadastrado com este documento.',
            'phone.required' => 'O telefone do paciente é obrigatório.',
            'phone.string' => 'O telefone do paciente deve ser um texto.',
            'phone.min' => 'O telefone do paciente deve ter pelo menos :min caracteres.',
            'phone.max' => 'O telefone do paciente deve ter no máximo :max caracteres.',
            'gender.required' => 'O gênero do paciente é obrigatório.',
            'gender.enum' => 'O gênero informado é inválido.',
            'email.required' => 'O e-mail do paciente é obrigatório.',
            'email.string' => 'O e-mail do paciente deve ser um texto.',
            'email.email' => 'Informe um e-mail válido para o paciente.',
            'email.max' => 'O e-mail do paciente deve ter no máximo :max caracteres.',
            'day.required' => 'O dia da semana é obrigatório.',
            'day.integer' => 'O dia da semana deve ser um número inteiro.',
            'day.min' => 'O dia da semana deve ser maior ou igual a :min.',
            'active.required' => 'Informe se o paciente está ativo.',
            'active.boolean' => 'A opção de paciente ativo deve ser verdadeira ou falsa.',
            'create_appointments.required' => 'Informe se as consultas devem ser criadas automaticamente.',
            'create_appointments.boolean' => 'A opção de criar consultas automaticamente deve ser verdadeira ou falsa.',
            'notify_appointment.boolean' => 'A opção de notificar sobre consultas deve ser verdadeira ou falsa.',
            'notes.string' => 'As observações devem ser um texto.',
            'notes.max' => 'As observações devem ter no máximo :max caracteres.',
            'type.required' => 'A frequência das consultas é obrigatória.',
            'type.enum' => 'A frequência das consultas informada é inválida.',
            'time.required' => 'A duração das consultas é obrigatória.',
            'time.enum' => 'A duração das consultas informada é inválida.',
            'value.numeric' => 'O valor da consulta deve ser um número.',
        ];
    }
}
