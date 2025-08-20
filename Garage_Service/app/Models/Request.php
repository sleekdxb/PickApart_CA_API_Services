<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'Requests';

    // The primary key for the model
    protected $primaryKey = 'id';

    // Define the type of the primary key
    protected $keyType = 'string';

    // Auto increment should be used for the primary key
    public $incrementing = true;

    // Define the attributes that are mass assignable
    protected $fillable = [
        'part_id',
        'request_id',
        'vend_id',
        'sender_acc_id',
        'vend_acc_id',
        'message'
    ];

    // Define the timestamps (created_at, updated_at)
    public $timestamps = true;

    // Define the date format for timestamps if necessary
    protected $dateFormat = 'Y-m-d H:i:s';

    // Cast attributes to specific types
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Define relationships (if any) – for example, if there's a relationship with users, you can define them here
}
