<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'subVendorAccess';

    // Define the primary key
    protected $primaryKey = 'id';

    // Disable automatic timestamp management since we have custom timestamps (created_at, updated_at)
    public $timestamps = true;

    // Define the fillable fields (for mass assignment)
    protected $fillable = [
        'priv_id', 
        'vend_id', 
        'acc_id', 
        'sub_ven_id',
        'privilege', 
        'state'
    ];

    // If you have any casts for specific fields (e.g., casting 'privilege_code' to an integer):
    protected $casts = [
        'privilege_code' => 'integer',
    ];
}
