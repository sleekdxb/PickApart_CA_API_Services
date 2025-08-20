<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    // Set the table name
    protected $table = 'parts';

    // Set the primary key
    protected $primaryKey = 'id'; // Default is 'id', but you can set another if needed

    // Disable timestamps if you don't need them
    // public $timestamps = false;

    // Define the fillable or guarded properties to prevent mass assignment issues
    protected $fillable = [
        'inve_id',
        'part_id',
        'sub_ven_id',
        'vend_id',
        'make_id',
        'model_id',
        'cat_id',
        'sub_cat_id',
        'price',
        'year',
        'sale_price',
        'retail_price',
        'description',
    ];

   

}
