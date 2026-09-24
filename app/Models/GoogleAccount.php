<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAccount extends Model
{
    protected $table = 'public.google_accounts';

    protected $fillable = [
        'user_id',
        'refresh_token',
    ];
}
