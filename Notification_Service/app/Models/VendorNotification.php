<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorNotification extends Model
{
    protected $table = 'notifications_vendor';

    protected $fillable = [
        'acc_id',
        'vend_id',
        'notifiable_id',
        'type',
        'data',
        'read',
        'read_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];
}
