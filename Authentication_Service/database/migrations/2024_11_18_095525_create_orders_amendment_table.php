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
        Schema::create('orders_amendment', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT)
            $table->id(); // Primary key (auto-incrementing)

            // `order_id`: References the original order from the `orders` table
           
            $table->string('order_id', 255);
            // `amendment_date`: The date when the amendment was made
            $table->date('amendment_date');

            // `status`: Status of the amendment (e.g., 'pending', 'approved')
            $table->string('status');

            // `amendment_details`: Store the details of the amendments in JSON format
            $table->json('amendment_details'); // JSON to store amendment changes (e.g., updated parts, amounts)

            // Timestamps for created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key constraints
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');

            // Optional: Index the `order_id` column for better query performance
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders_amendment');
    }
};
