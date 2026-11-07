<?php

namespace App\Domains\Report\Entities;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property int|null $patient_id
 * @property Carbon $reference_month
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property ReportStatusEnum $status
 * @property string|null $batch_id
 * @property int $total_patients
 * @property int $processed_patients
 * @property string|null $file_path
 * @property string|null $error_message
 * @property Carbon|null $completed_at
 * @property-read Patient|null $patient
 */
class Report extends Model
{
    use HasUuid;

    protected $hidden = ['id', 'patient_id'];

    protected $table = 'reports';

    protected $fillable = [
        'user_id',
        'patient_id',
        'reference_month',
        'period_start',
        'period_end',
        'status',
        'batch_id',
        'total_patients',
        'processed_patients',
        'file_path',
        'error_message',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_month' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => ReportStatusEnum::class,
            'total_patients' => 'integer',
            'processed_patients' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function periodStartDate(): Carbon
    {
        return ($this->period_start ?? $this->reference_month)->copy()->startOfMonth();
    }

    public function periodEndDate(): Carbon
    {
        return ($this->period_end ?? $this->reference_month)->copy()->endOfMonth();
    }

    public function periodLabel(): string
    {
        $start = $this->periodStartDate();
        $end = $this->periodEndDate();

        if ($start->isSameMonth($end)) {
            return $start->format('m/Y');
        }

        return $start->format('d/m/Y').' a '.$end->format('d/m/Y');
    }
}
