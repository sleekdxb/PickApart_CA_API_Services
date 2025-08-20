<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;
    // The name of the table
    protected $table = 'otps';

    // Set the primary key for the model (if different from the default 'id')
    protected $primaryKey = 'id';

    // The attributes that are mass assignable
    protected $fillable = [
        'otp_id',
        'acc_id',
        'otp',
        'sub_vend_id',
        'is_used',
        'expires_at',
        'device_info'
    ];

    // Cast attributes to their respective data types
    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
