<?php

namespace App\Data\WaitList;

use App\Domains\Patient\Entities\WaitList;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MapName(SnakeCaseMapper::class)]
class WaitListData extends Data
{
    public function __construct(
        public ?string $uuid,
        public string $patientUuid
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'patient_uuid' => [
                'required',
                'uuid',
            ],
        ];
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $patientUuid = $validator->getData()['patient_uuid'] ?? null;

            if (! $patientUuid || $validator->errors()->has('patient_uuid')) {
                return;
            }

            $isAlreadyWaiting = WaitList::query()
                ->whereHas('patient', fn ($query) => $query->where('uuid', $patientUuid))
                ->exists();

            if ($isAlreadyWaiting) {
                $validator->errors()->add(
                    'patient_uuid',
                    'Paciente já está na lista de espera.'
                );
            }
        });
    }
}
