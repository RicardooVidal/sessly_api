<?php

namespace App\Data\Contract;

use App\Data\Casts\CarbonCast;
use App\Data\Patient\PatientData;
use App\Domains\Contract\Entities\Contract;
use App\Rules\PatientWithContractRule;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MapName(SnakeCaseMapper::class)]
class ContractData extends Data
{
    public function __construct(
        #[FromRouteParameterProperty('contract')]
        public ?string $uuid = null,
        public ?string $description = null,
        public PatientData|Optional $patient,
        #[WithCast(CarbonCast::class)]
        public ?Carbon $validUntil = null,
        public ?string $documentUrl = null,
        public null|UploadedFile|Optional $file = null,
        public ?Carbon $createdAt = null,
        public ?Carbon $updatedAt = null,
    )
    {}

    public static function fromModel(Contract $contract): self
    {
        return new self(
            uuid: $contract->uuid,
            description: $contract->description,
            patient: $contract->relationLoaded('patient')
                ? PatientData::from($contract->patient->only(['id', 'uuid', 'name']))
                : Optional::create(),
            validUntil: $contract->valid_until,
            documentUrl: $contract->document_url,
            createdAt: $contract->created_at,
            updatedAt: $contract->updated_at,
        );
    }

    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $data = $validator->getData();
            $patient = data_get($data, 'patient.uuid');

            if (!$patient) {
                return;
            }

            $rule = new PatientWithContractRule();
            $rule->validate('patient', $patient, function (string $message) use ($validator): void {
                $validator->errors()->add('patient', $message);
            });
        });
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'patient' => [
                'required',
            ],
            'description' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'valid_until' => [
                'required',
                'date',
               'after:now'
            ],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx',
            ],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'valid_until.after' => 'A data de validade do contrato deve ser posterior a hoje',
            'file.required' => 'O arquivo do contrato é obrigatório',
            'file.mimes' => 'Tipos suportados: pdf, doc, docx, xls e xlsx'
        ];
    }
}
