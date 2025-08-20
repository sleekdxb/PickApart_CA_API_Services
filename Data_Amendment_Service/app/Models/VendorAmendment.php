<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorAmendment extends Model
{
    protected $table = 'amendment_vendor'; // Your actual table name

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'staff_id',
        'acc_id',
        'amendment_type',
        'change_request',
        'original_data',
        'updated_data',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
