<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartName extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Specify the table if it doesn't follow Laravel's naming convention
    protected $table = 'PartsName';

    // Define the fillable properties for mass assignment
    protected $fillable = [
        'model_part_id',
        'cat_id',
        'part_name_id',
        'name',
    ];

    // Optionally, you can specify the date format or specify the columns for timestamps
    protected $dates = ['created_at', 'updated_at'];

    public function part()
    {
        return $this->belongsTo(Part::class, 'cat_id'); // vend_id is the foreign key
    }

}
