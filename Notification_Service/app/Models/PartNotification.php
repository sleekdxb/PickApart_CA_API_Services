<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartNotification extends Model
{
    protected $table = 'part_notifications';

    protected $fillable = [
        'acc_id',
        'vend_id',
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
