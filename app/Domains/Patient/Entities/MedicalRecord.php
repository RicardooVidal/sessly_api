<?php

namespace App\Domains\Patient\Entities;

use App\Models\Traits\HasUuid;
use Carbon\Carbon;
use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $patient_id
 * @property string $record
 * @property string $document
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static MedicalRecordFactory factory($count = null, $state = [])
 */
class MedicalRecord extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $hidden = ['id'];

    protected $table = 'medical_records';

    protected $fillable = [
        'record',
        'document',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public static function newFactory(): MedicalRecordFactory
    {
        return MedicalRecordFactory::new();
    }
}
