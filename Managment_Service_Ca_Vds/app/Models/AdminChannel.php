<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminChannel extends Model
{
    protected $table = 'admin_channels';

    protected $fillable = [
        'uuid',
        'data',
        'channel_frequency',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Automatically generate uuid and channel_requsncy on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->channel_frequency = Str::random(16);
        });
    }

}
