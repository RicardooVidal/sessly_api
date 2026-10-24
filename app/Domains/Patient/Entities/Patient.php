<?php

namespace App\Domains\Patient\Entities;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Enums\GenderEnum;
use App\Models\Traits\HasUuid;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property-read int $id
 * @property string $uuid
 * @property string $name
 * @property string $document
 * @property bool $active
 * @property bool $create_appointments
 * @property bool $notify_appointment
 * @property string|null $notes
 * @property bool $done
 * @property float $value
 * @property AppointmentTimeEnum $time,
 * @property AppointmentTypeEnum $type,
 * @property int $day,
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property MedicalRecord $medicalRecord
 *
 * @method static PatientFactory factory($count = null, $state = [])
 */
class Patient extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $hidden = ['id'];

    protected $fillable = [
        'name',
        'document',
        'active',
        'create_appointments',
        'notify_appointment',
        'day',
        'phone',
        'gender',
        'email',
        'notes',
        'type',
        'time',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'gender' => GenderEnum::class,
            'type' => AppointmentTypeEnum::class,
            'time' => AppointmentTimeEnum::class,
            'value' => 'float',
        ];
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public static function newFactory(): PatientFactory
    {
        return PatientFactory::new();
    }
}
