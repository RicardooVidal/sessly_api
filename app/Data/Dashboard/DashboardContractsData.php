<?php

namespace App\Data\Dashboard;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
class DashboardContractsData extends Data
{
    public function __construct(
        public int $expiringInThirtyDays,
    ) {}
}
