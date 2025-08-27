<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageSession extends Model
{
    use HasFactory;

    protected $table = 'Garage_Sessions';

    protected $fillable = [
        'session_id',
        'acc_id',
        'end_time',
        'ipAddress',
        'isActive',
        'session_type',
        'lastAccessed',
        'access_token',
        'fcm_token',
        'sessionData',
        'start_time',
        'life_time',
    ];

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

    public function scopeActive($query)
    {
        return $query->where('isActive', 1);
    }
}
