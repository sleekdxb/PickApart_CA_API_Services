<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Memberships extends Model
{
    protected $table = 'memberships';

    protected $fillable = [
        'memb_id',
        'transaction_id',
        'acc_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'allowance_level',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'model_id'); // vend_id is the foreign key
    }


    public function payments()
    {
        return $this->hasMany(Payment::class, 'transaction_id', 'transaction_id');
    }
}


