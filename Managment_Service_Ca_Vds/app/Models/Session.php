<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $table = 'sessions'; // Change this if your table name is different

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'session_id',
        'acc_id',
        'end_time',
        'ipAddress',
        'isActive',
        'lastAccessed',
        'sessionData',
        'start_time',
        'life_time',
    ];

    protected $casts = [
        'end_time' => 'datetime',
        'lastAccessed' => 'datetime',
        'start_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'isActive' => 'boolean',
    ];
    public function account()
    {
        return $this->belongsTo(Account::class, 'acc_id'); // vend_id is the foreign key
    }
}
