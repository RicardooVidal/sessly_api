<?php

namespace App\Domains\Appointment\Entities;

use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use App\Models\Traits\HasUuid;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property int $patient_id
 * @property Carbon $date
 * @property PresenceEnum $presence
 * @property float|null $value
 * @property string|null $notes
 * @property string|null $google_event_id
 * @property string|null $google_meet_url
 * @property-read Patient $patient
 *
 * @method static AppointmentFactory factory($count = null, $state = [])
 */
class Appointment extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $hidden = ['id'];

    protected $table = 'appointments';

    protected $fillable = [
        'date',
        'presence',
        'value',
        'notes',
        'google_event_id',
        'google_meet_url',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'value' => 'float',
            'presence' => PresenceEnum::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }
}
