<?php

namespace App\Data\Dashboard;

use App\Data\SelectData;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class DashboardNextAppointmentData extends Data
{
    public function __construct(
        public string $uuid,
        public Carbon $date,
        public PresenceEnum $presence,
        public SelectData $patient,
    ) {}
}
