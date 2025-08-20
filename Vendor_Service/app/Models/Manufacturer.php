<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory;

    // Specify the table name if it's different from the pluralized model name
    protected $table = 'manufacturers';

    // Define which fields are mass assignable
    protected $fillable = ['cat_id', 'name'];

    // Timestamps are enabled by default in Laravel, so no need to specify if the model uses them
    // However, if you have different column names for created_at or updated_at, you would specify them here:
    // const CREATED_AT = 'created_at_column_name';
    // const UPDATED_AT = 'updated_at_column_name';
    public function model()
    {
        return $this->hasMany(CarModel::class, 'make_id', 'make_id'); // vend_id is the foreign key
    }
}
