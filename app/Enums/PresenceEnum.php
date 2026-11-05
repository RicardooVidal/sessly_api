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

    #[Description('Presente - A receber')]
    case PRESENT_TO_RECEIVE = 'present_to_receive';

    #[Description('Ausente')]
    case ABSENT = 'absent';

    public static function presentStatuses(): array
    {
        return [
            self::PRESENT,
            self::PRESENT_TO_RECEIVE,
        ];
    }

    public static function statusForCreateNewAppointmentAutomatically(): array
    {
        return [
            self::PRESENT,
            self::PRESENT_TO_RECEIVE,
            self::ABSENT,
        ];
    }
}
