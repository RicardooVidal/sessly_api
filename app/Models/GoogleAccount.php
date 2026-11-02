<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAccount extends Model
{
    protected $table = 'public.google_accounts';

    public function getTable()
    {
        return $this->getConnection()->getDriverName() === 'sqlite'
            ? 'google_accounts'
            : parent::getTable();
    }

    protected $fillable = [
        'user_id',
        'refresh_token',
    ];
}
