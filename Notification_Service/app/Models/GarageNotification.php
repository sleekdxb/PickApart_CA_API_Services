<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarageNotification extends Model
{
    protected $table = 'notifications_garage';

    protected $fillable = [
        'acc_id',
        'gra_id',
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
