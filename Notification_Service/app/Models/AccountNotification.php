<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountNotification extends Model
{
    protected $table = 'account_notifications';

    protected $fillable = [
        'acc_id',
        'id',
        'notifiable_id',
        'type',
        'data',
        'read',
        'read_at',
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];
}
