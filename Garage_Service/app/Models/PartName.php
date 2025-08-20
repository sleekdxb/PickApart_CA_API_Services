<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartName extends Model
{
    use HasFactory;

    // Optionally specify the table name if it's different from the pluralized model name
    protected $table = 'PartsName';

    // Fillable or guarded attributes for mass assignment
    protected $fillable = [
        'model_part_id',
        'cat_id',
        'part_name_id',
        'name',
        'created_at',
        'updated_at',
    ];

    // If you want to explicitly handle created_at & updated_at columns
    public $timestamps = true;
    public function part()
    {
        return $this->belongsTo(Part::class, 'model_part_id'); // vend_id is the foreign key
    }
}
