<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('parts', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT can support up to 300,000,000)
            $table->id(); // This creates an auto-incrementing primary key column (BIGINT)

            // `inve_id`: Hashed value linking to the Inventory table
            $table->string('inve_id', 255);
            $table->string('part_id', 255)->unique(); // Assuming `inve_id` is a hashed value

            // `name`: Name of the part
            $table->string('name');

            // `color`: Color of the part
            $table->string('color')->nullable(); // Optional field

            // `class`: Class of the part (e.g., Engine, Body)
            $table->string('class');

            // `grade`: Grade or quality of the part (e.g., A, B, OEM, Aftermarket)
            $table->string('grade');

            // `make`: Manufacturer or make of the part (e.g., Toyota, Ford)
            $table->string('make');

            // `model`: Model of the part (e.g., Camry, Mustang)
            $table->string('model');

            // Add a `price` column to store the part price.
            // We'll use `decimal` to store the price with 2 decimal places.
            $table->decimal('price', 15, 2)->nullable();

            // `vin_number`: VIN (Vehicle Identification Number) associated with the part
            $table->string('vin_number')->nullable(); // Optional field

            // `year`: Year of the part
            $table->year('year');

            // `sub_class`: Subclass/category of the part (optional, can be NULL)
            $table->string('sub_class')->nullable();

            // `map_location`: Location of the part on the map (latitude and longitude)
            $table->string('map_location')->nullable(); // Optional field

            // Timestamps: created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key linking `inve_id` to the `inventory` table
            $table->foreign('inve_id')->references('inve_id')->on('inventory')->onDelete('cascade');

            // Optional: Index the `inve_id` column for faster queries
            $table->index('inve_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parts');
    }
};
