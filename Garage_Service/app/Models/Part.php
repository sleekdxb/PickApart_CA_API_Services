<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Image;
use App\Models\PartCategory;

class Part extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    // Define the relationship where Part belongs to Vendor

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
        'stock_id',
        'quantity',
        'sale_price',
        'retail_price',
        'description',
    ];


    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }
    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }

    public function image()
    {
        return $this->hasMany(Image::class, 'part_id', 'part_id'); // vend_id is the foreign key
    }

    public function partCategory()
    {
        return $this->hasOne(PartCategory::class, 'cat_id', 'cat_id'); // vend_id is the foreign key
    }

    public function partName()
    {
        return $this->hasOne(PartName::class, 'model_part_id', 'sub_cat_id'); // vend_id is the foreign key
    }
    public function notification()
    {
        return $this->hasMany(Notification::class, 'acc_id', 'acc_id'); // vend_id is the foreign key
    }

}
