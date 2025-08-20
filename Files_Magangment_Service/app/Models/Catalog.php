<?php

namespace App\Models;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    // Specify the table name (if it's not the plural form of the model name)
    protected $table = 'catalogs';

    // Specify the columns that can be mass-assigned
    protected $fillable = [
        'id',
        'vin',
        'region',
        'manufacturer',
        'model',
        
    ];

    // Disable timestamps if your table doesn't use them (optional)
   
    // Optionally, you can set the name of the created_at and updated_at columns
    // const CREATED_AT = 'creation_date';
    // const UPDATED_AT = 'last_updated';

    // If you want to automatically generate a cata_id upon model creation, you could use a boot method (optional)
    
}
