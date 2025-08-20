<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarageNotifictionsChannle extends Model
{
    use HasFactory;

    /** Table name */
    protected $table = 'GarageNotifictionsChannle';

    /** Primary key settings */
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /** created_at / updated_at */
    public $timestamps = true;

    /** Mass-assignable fields */
    protected $fillable = [
        'channel_name',
        'acc_id',
        'channel_frequency',
        'latest_data',
        'created_at',
        'updated_at',
    ];

    /** Casts (assumes latest_data stores JSON) */
    protected $casts = [
        'latest_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


}
