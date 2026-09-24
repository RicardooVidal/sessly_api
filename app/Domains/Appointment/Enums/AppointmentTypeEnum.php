<?php

namespace App\Domains\Appointment\Enums;

use App\Attributes\Description;
use App\Traits\WithDescription;

enum AppointmentTypeEnum: string
{
    use WithDescription;

    #[Description('Diário')]
    case DAILY = 'daily';

    #[Description('Semanal')]
    case WEEKLY = 'weekly';

    #[Description('Quinzenal')]
    case BI_WEEKLY = 'bi-weekly';

    #[Description('Mensal')]
    case MONTHLY = 'monthly';
}
