<?php

namespace App\Data\Report;

use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class IndexReportData extends Data
{
    public function __construct(
        public ?string $month = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $patientUuid = null,
    ) {}

    public function isPeriod(): bool
    {
        return $this->startDate !== null || $this->endDate !== null;
    }

    public function periodStart(): Carbon
    {
        return $this->isPeriod()
            ? Carbon::parse($this->startDate)->startOfMonth()
            : Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
    }

    public function periodEnd(): Carbon
    {
        return $this->isPeriod()
            ? Carbon::parse($this->endDate)->endOfMonth()
            : Carbon::createFromFormat('Y-m', $this->month)->endOfMonth();
    }

    public static function rules(): array
    {
        return [
            'month' => [
                'nullable',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
                'required_without_all:start_date,end_date',
                'prohibits:start_date,end_date',
            ],
            'start_date' => [
                'nullable',
                'date_format:Y-m-d',
                'required_without:month',
                'required_with:end_date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'required_without:month',
                'required_with:start_date',
                'after_or_equal:start_date',
            ],
            'patient_uuid' => [
                'nullable',
                'uuid',
                Rule::exists('patients', 'uuid'),
            ],
        ];
    }

    public static function messages(...$args): array
    {
        return [
            'month.required_without_all' => 'Informe o mês ou o período.',
            'month.regex' => 'O mês deve estar no formato AAAA-MM.',
            'month.prohibits' => 'Informe o mês ou o período, mas não ambos.',
            'start_date.date_format' => 'A data inicial deve estar no formato AAAA-MM-DD.',
            'start_date.required_without' => 'Informe a data inicial do período.',
            'start_date.required_with' => 'Informe a data inicial do período.',
            'start_date.before_or_equal' => 'A data inicial deve ser anterior ou igual à data final.',
            'end_date.date_format' => 'A data final deve estar no formato AAAA-MM-DD.',
            'end_date.required_without' => 'Informe a data final do período.',
            'end_date.required_with' => 'Informe a data final do período.',
            'end_date.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
            'patient_uuid.uuid' => 'O paciente informado é inválido.',
            'patient_uuid.exists' => 'O paciente informado não foi encontrado.',
        ];
    }
}
