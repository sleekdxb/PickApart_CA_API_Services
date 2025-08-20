<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Specify the table name (since Laravel will default to "manufacturers" for the plural of the model name)
    protected $table = 'manufacturers';

    // Define the fillable properties for mass assignment
    protected $fillable = [
        'make_id',
        'name',
    ];

    // Optionally, you can specify the date format or specify the columns for timestamps
    protected $dates = ['created_at', 'updated_at'];



    public function part()
    {
        return $this->belongsTo(Part::class, 'make_id'); // vend_id is the foreign key
    }
}
