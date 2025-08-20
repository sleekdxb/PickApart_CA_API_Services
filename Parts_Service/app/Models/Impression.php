<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impression extends Model
{
    use HasFactory;

    // Table name (Laravel will assume it's the plural form of the model name, but we define it explicitly)
    protected $table = 'impressions';

    // Primary key (Laravel will assume 'id' is the primary key, but we define it explicitly)
    protected $primaryKey = 'id';

    // Disable timestamps if you are managing created_at and updated_at manually (not needed here, since Laravel handles them automatically)
    public $timestamps = true;

    // Define which columns are mass assignable (for the bulk insert/update)
    protected $fillable = [
        'imp_id',
        'doer_id',
        'vend_id',
        'part_id',
        'acc_id',
        'type',
        'value',
        'rating',
        'review'
    ];

    // Define the columns that should be cast to a specific type (optional, but useful for casting)
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
