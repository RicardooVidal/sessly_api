<?php

namespace App\Domains\Report\Enums;

use App\Attributes\Description;
use App\Traits\WithDescription;

enum ReportStatusEnum: string
{
    use WithDescription;

    #[Description('Na fila')]
    case QUEUED = 'queued';

    #[Description('Gerando')]
    case PROCESSING = 'processing';

    #[Description('Concluído')]
    case COMPLETED = 'completed';

    #[Description('Falhou')]
    case FAILED = 'failed';

    #[Description('Cancelado')]
    case CANCELLED = 'cancelled';

    public function isFinished(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
        ], true);
    }
}
