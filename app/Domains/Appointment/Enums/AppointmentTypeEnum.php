<?php

namespace App\Domains\Appointment\Enums;

use App\Attributes\Days;
use App\Attributes\Description;
use App\Traits\WithDays;
use App\Traits\WithDescription;

enum AppointmentTypeEnum: string
{
    use WithDescription;
    use WithDays;

    #[Description('Diário')]
    #[Days(1)]
    case DAILY = 'daily';

    #[Description('Semanal')]
    #[Days(7)]
    case WEEKLY = 'weekly';

    #[Description('Quinzenal')]
    #[Days(15)]
    case BI_WEEKLY = 'bi-weekly';

    #[Description('Mensal')]
    #[Days(30)]
    case MONTHLY = 'monthly';
}
