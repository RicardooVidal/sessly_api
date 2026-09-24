<?php

namespace App\Data\Appointment;

use App\Data\Casts\CarbonCast;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class IndexAppointmentData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $patientUuid = null,
        #[WithCast(CarbonCast::class)]
        public ?Carbon $date = null,
        public ?AppointmentTypeEnum $type = null,
        public ?AppointmentTimeEnum $time = null,
    ) {}

    public static function rules(): array
    {
        return [
            'uuid' => [
                'nullable',
                'uuid',
            ],
            'patient_uuid' => [
                'nullable',
                'uuid',
            ],
            'date' => [
                'nullable',
                'date',
            ],
            'type' => [
                'nullable',
                Rule::enum(AppointmentTypeEnum::class)
            ],
            'time' => [
                'nullable',
                Rule::enum(AppointmentTimeEnum::class)
            ],
        ];
    }
}
