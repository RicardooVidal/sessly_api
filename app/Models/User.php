<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read string $id
 * @property-read string $uuid
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $company_id
 * @property Company $company
 * @property Carbon $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static UserFactory factory($count = null, $state = [])
 */
class User extends Authenticatable
{
    use HasApiTokens, HasUuid, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'company_id'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $table = 'users';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
