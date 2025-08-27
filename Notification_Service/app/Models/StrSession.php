<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrSession extends Model
{
    use HasFactory;

    protected $table = 'STR_Sessions';

    protected $fillable = [
        'session_id',
        'acc_id',
        'end_time',
        'ipAddress',
        'isActive',
        'lastAccessed',
        'session_type',
        'access_token',
        'fcm_token',
        'sessionData',
        'start_time',
        'life_time',
    ];

    // Hide sensitive fields from JSON/array output
    protected $hidden = [
        'ipAddress',
        'sessionData',
    ];

    protected $casts = [
        'end_time' => 'datetime',
        'lastAccessed' => 'datetime',
        'start_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'isActive' => 'boolean',
        'life_time' => 'integer',
    ];

    public $timestamps = true;

    public function account()
    {
        return $this->belongsTo(Account::class, 'acc_id', 'id');
    }

    // Quick filter for active sessions
    public function scopeActive($query)
    {
        return $query->where('isActive', 1);
    }
}
