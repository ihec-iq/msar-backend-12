<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupAdmin extends Model
{
    protected $fillable = [
        'name',
        'email',
        'telegram_id',
        'active',
        'notify_via',
    ];

    protected $casts = [
        'active' => 'boolean',
        'notify_via' => 'array',
    ];
}
