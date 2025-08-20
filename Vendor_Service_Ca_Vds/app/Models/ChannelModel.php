<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelModel extends Model
{
    use HasFactory;

    // Define the table name if it's not the plural form of the model name
    protected $table = 'channels';

    // Define which fields are mass assignable
    protected $fillable = ['channel_name', 'vendor_id', 'channel_frequency', 'latest_data','created_at', 'updated_at'];

    // If the vendor_id is referencing a Vendor model, define the relationship
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id'); // vend_id is the foreign key
    }
}
