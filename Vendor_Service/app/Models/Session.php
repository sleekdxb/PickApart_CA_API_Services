<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    protected $table = 'sessions';  // Name of your custom session table

    protected $fillable = [
        'session_id',
        'acc_id',
        'ipAddress',
        'isActive',
        'lastAccessed',
        'sessionData', 
        'start_time', 
        'end_time',
        'acc_agent_info',
        'created_at',
        'updated_at'
    ];
  protected $casts = [
        'sessionData' => 'array', // Convert sessionData to array when retrieved
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'lastAccessed' => 'datetime'
    ];
    public $timestamps = false; // assuming you are using custom timestamps, not Laravel's default

    // You can add custom methods for your session logic here
}
