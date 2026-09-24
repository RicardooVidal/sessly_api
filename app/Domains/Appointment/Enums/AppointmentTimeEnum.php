<?php

namespace App\Domains\Appointment\Enums;

enum AppointmentTimeEnum: int
{
    case TWENTY_MINUTES = 20;
    case THIRTY_MINUTES = 30;
    case FORTHY_MINUTES = 40;
    case FIFTY_MINUTES = 50;
    case ONE_HOUR = 60;
}
