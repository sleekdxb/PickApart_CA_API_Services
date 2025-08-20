<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impression extends Model
{
    protected $table = 'impressions'; // explicitly define table name

    protected $fillable = [
        'imp_id',
        'type',
        'value',
        'part_id',
        'vend_id',
        'acc_id',
        'created_at',
        'updated_at',
    ];

   public function part()
    {
        return $this->belongsTo(Part::class, 'part_id'); // vend_id is the foreign key
    }
}
