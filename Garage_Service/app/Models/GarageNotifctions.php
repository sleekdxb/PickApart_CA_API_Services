<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarageNotifctions extends Model
{
    protected $table = 'garage_notifctions';

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
        'data' => 'array',     // if you store JSON in text
        'read' => 'boolean',
        'read_at' => 'datetime',
    ];


    public function garage()
    {
        return $this->belongsTo(Garage::class, 'gra_id'); // vend_id is the foreign key
    }
}
