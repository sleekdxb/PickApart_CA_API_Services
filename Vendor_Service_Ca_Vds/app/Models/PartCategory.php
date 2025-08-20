<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartCategory extends Model
{
    use HasFactory;

    // Optionally specify the table name if it's different from the pluralized model name
    protected $table = 'partscategory';

    // Fillable or guarded attributes for mass assignment
    protected $fillable = [
        'cat_id',
        'name',
        'created_at',
        'updated_at',
    ];

    // If you want to explicitly handle created_at & updated_at columns
    public $timestamps = true;

    public function part()
    {
        return $this->belongsTo(Part::class, 'cat_id'); // vend_id is the foreign key
    }

    public function subParts()
    {
        return $this->hasMany(PartName::class, 'part_id', 'part_id'); // vend_id is the foreign key
    }

}
