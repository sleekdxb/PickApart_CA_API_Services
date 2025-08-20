<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmendmentVendor extends Model
{
    protected $table = 'amendment_vendor'; // Use correct table name

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = true;

    protected $fillable = [
        'staff_id',
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

    public function account()
    {
        return $this->belongsTo(Account::class, 'acc_id'); // vend_id is the foreign key
    }
}
