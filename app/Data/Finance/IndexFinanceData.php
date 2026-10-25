<?php

namespace App\Data\Finance;

use App\Data\Casts\CarbonCast;
use App\Domains\Finance\Enums\FinancePeriodEnum;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class IndexFinanceData extends Data
{
    public function __construct(
        public ?FinancePeriodEnum $period = null,
        #[WithCast(CarbonCast::class)]
        public ?Carbon $date = null,
    ) {}

    public static function rules(): array
    {
        return [
            'period' => [
                'nullable',
                Rule::enum(FinancePeriodEnum::class),
            ],
            'date' => [
                'nullable',
                'date',
            ],
        ];
    }
}
