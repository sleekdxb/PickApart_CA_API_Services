<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Seller extends Model
{
    use HasFactory;
     // The name of the table
    protected $table = 'subvendors';

    // Set the primary key for the model (if different from the default 'id')
    protected $primaryKey = 'id';

    // The attributes that are mass assignable
    protected $fillable = [
        'vend_id',
        'acc_id', 
        'access_type', 
        'email', 
        'password', 
        'phone',
        'first_name',
        'last_name',
    ];

}
