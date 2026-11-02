<?php

namespace App\Enums;

use App\Attributes\Description;
use App\Traits\WithDescription;

enum PresenceEnum: string
{
    use WithDescription;

    #[Description('Pendente')]
    case PENDING = 'pending';

    #[Description('Presente')]
    case PRESENT = 'present';

    #[Description('Ausente')]
    case ABSENT = 'absent';

    public static function statusForCreateNewAppointmentAutomatically(): array
    {
        return [
          self::PRESENT,
          self::ABSENT,
        ];
    }
}
