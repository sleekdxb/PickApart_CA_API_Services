<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'memberships';

    // Define the primary key column
    protected $primaryKey = 'id';

    // Specify if the primary key is auto-incrementing (Laravel assumes true by default)
    public $incrementing = true;

    // Define the data types for the columns
    protected $keyType = 'int';

    // Define the fields that are mass assignable
    protected $fillable = [
        'memb_id',
        'transaction_id',
        'acc_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'allowance_level',
        'created_at'
    ];

    // Disable timestamps if you don't want Laravel to automatically manage created_at and updated_at
// public $timestamps = false;

    // Define the date columns (for date casting)
    protected $dates = [
        'start_date',
        'end_date',
    ];

    // Define relationships to other tables if necessary (example below)
// public function user() {
// return $this->belongsTo(User::class, 'memb_id', 'id');
// }

    public function membership()
    {
        return $this->belongsTo(Vendor::class, 'vend_id'); // vend_id is the foreign key
    }
}