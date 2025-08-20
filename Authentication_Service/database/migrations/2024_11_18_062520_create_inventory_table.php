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
         Schema::create('inventory', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT can support up to 300,000,000)
            $table->id(); // This creates an auto-incrementing primary key column (BIGINT)
            $table->string('inve_id', 255)->unique(); // Assuming `vendor_id` is a hashed value
            $table->string('vend_id', 255);
            // `vendor_id`: Hashed value linking to the Vendor table
           

            // `inve_class`: Inventory class (hashed value or string)
            $table->string('inve_class'); // Class of the inventory item

            // `itemsIn`: Quantity of items added to the inventory
            $table->integer('itemsIn')->default(0); // Default to 0

            // `itemsOut`: Quantity of items removed from the inventory
            $table->integer('itemsOut')->default(0); // Default to 0

            // `inve_type`: Type of inventory (e.g., Raw Materials, Finished Goods)
            $table->string('inve_type');

            // `inve_description`: Description of the inventory item
            $table->text('inve_description')->nullable(); // Optional description field

            // Timestamps: created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key linking `vendor_id` to the `vendors` table
            $table->foreign('vend_id')->references('vend_id')->on('vendors')->onDelete('cascade');

            // Optional: Index the `vendor_id` column for faster queries
            $table->index('vend_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory');
    }
};
