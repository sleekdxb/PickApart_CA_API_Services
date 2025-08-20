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
         Schema::create('orders', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT can support up to 300,000,000)
            $table->id(); // This creates an auto-incrementing primary key column (BIGINT)

            // `vendor_id`: Hashed value linking to the Vendor table
            $table->string('vend_id', 255); // Assuming `vendor_id` is a hashed value
            $table->string('order_id', 255)->unique();
            // `inve_id`: Hashed value linking to the Inventory table
            $table->string('inve_id', 255); // Assuming `inve_id` is a hashed value

            // `cus_id`: Hashed value linking to the Customers table
            $table->string('cus_id', 255); // Assuming `cus_id` is a hashed value

            // `parts_id`: Storing the part IDs as an array in JSON format
            $table->json('parts_id'); // Using JSON to store an array of part IDs

            // `order_date`: Date when the order was placed
            $table->date('order_date');

            // `status`: The current status of the order (e.g., 'pending', 'completed')
            $table->string('status');

            // `items_number`: The number of items in the order
            $table->integer('items_number')->default(0);

            // `order_amount`: The total amount of the order
            $table->decimal('order_amount', 15, 2); // Using decimal to store amounts with two decimal places

            // `agent_vendor_note`: Array of agent's notes as JSON
            $table->json('agent_vendor_note')->nullable(); // Nullable because not all orders might have notes

            // `agent_action`: Array of agent's actions as JSON
            $table->json('agent_action')->nullable(); // Nullable because not all orders might have actions

            // Timestamps: created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign keys linking `vendor_id`, `inve_id`, and `cus_id` to respective tables
            $table->foreign('vend_id')->references('vend_id')->on('vendors')->onDelete('cascade');
            $table->foreign('inve_id')->references('inve_id')->on('inventory')->onDelete('cascade');
            $table->foreign('cus_id')->references('cus_id')->on('customers')->onDelete('cascade');

            // Optional: Index the foreign key columns for better query performance
            $table->index('vend_id');
            $table->index('inve_id');
            $table->index('cus_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
