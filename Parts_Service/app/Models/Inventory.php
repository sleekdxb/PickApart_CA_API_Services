<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Vendor;
use App\Models\Part;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Inventory extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // Optionally specify the table name if it's different from the pluralized model name
    protected $table = 'inventory';

    // Fillable attributes for mass assignment
    protected $fillable = [
        'inve_id',
        'vend_id',
        'inve_class',
        'created_at',
        'updated_at',

    ];

    // If you want to explicitly handle created_at & updated_at columns
    public $timestamps = true;
    //

    public function part()
    {
        return $this->belongsTo(Part::class, 'inve_id'); // vend_id is the foreign key
    }

}
