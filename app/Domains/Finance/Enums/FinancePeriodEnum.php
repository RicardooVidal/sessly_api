<?php

namespace App\Domains\Finance\Enums;

use Carbon\Carbon;

enum FinancePeriodEnum: string
{
    case WEEK = 'week';
    case MONTH = 'month';

    public function startsAt(Carbon $reference): Carbon
    {
        return match ($this) {
            self::WEEK => $reference->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            self::MONTH => $reference->copy()->startOfMonth()->startOfDay(),
        };
    }

    public function endsAt(Carbon $reference): Carbon
    {
        return match ($this) {
            self::WEEK => $reference->copy()->startOfWeek(Carbon::MONDAY)->endOfWeek(Carbon::SUNDAY)->endOfDay(),
            self::MONTH => $reference->copy()->endOfMonth()->endOfDay(),
        };
    }
}
