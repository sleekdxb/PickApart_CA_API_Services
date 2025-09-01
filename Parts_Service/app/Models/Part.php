<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Image;

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
        'stock_id',
        'quantity',
        'price',
        'year',
        'sale_price',
        'retail_price',
        'description',
    ];

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'inve_id', 'inve_id'); // vend_id is the foreign key
    }

    public function membership()
    {
        return $this->hasManyThrough(Membership::class, Vendor::class, 'vend_id', 'acc_id', 'vend_id', 'acc_id');
    }


    public function vendor_state()
    {
        return $this->hasMany(State::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }


    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'vend_id', 'vend_id'); // vend_id is the foreign key
    }

    public function image()
    {
        return $this->hasMany(Image::class, 'part_id', 'part_id'); // vend_id is the foreign key
    }

    public function carModel()
    {
        return $this->hasOne(CarModel::class, 'model_id', 'model_id'); // vend_id is the foreign key
    }

    public function partCategory()
    {
        return $this->hasOne(PartCategory::class, 'cat_id', 'cat_id'); // vend_id is the foreign key
    }

    public function manufacturer()
    {
        return $this->hasOne(Manufacturer::class, 'make_id', 'make_id'); // vend_id is the foreign key
    }
    public function partName()
    {
        return $this->hasOne(PartName::class, 'part_name_id', 'sub_cat_id'); // vend_id is the foreign key
    }




}
