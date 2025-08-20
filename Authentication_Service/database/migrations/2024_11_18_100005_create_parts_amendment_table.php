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
         Schema::create('parts_amendment', function (Blueprint $table) {
            // Create auto-incrementing `id` column (BIGINT)
            $table->id(); // Primary key (auto-incrementing)

            // `part_id`: References the original part from the `parts` table
            $table->string('part_id', 255); // Part ID (should be unique in parts table)

            // `amendment_date`: The date when the amendment was made
            $table->date('amendment_date');

            // `status`: Status of the amendment (e.g., 'pending', 'approved')
            $table->string('status');

            // `amendment_details`: Store the details of the amendments in JSON format
            $table->json('amendment_details'); // JSON to store amendment changes (e.g., updated price, color, class)

            // Timestamps for created_at and updated_at
            $table->timestamps(); // Automatically adds `created_at` and `updated_at`

            // Foreign key constraints
            $table->foreign('part_id')->references('part_id')->on('parts')->onDelete('cascade');

            // Optional: Index the `part_id` column for better query performance
            $table->index('part_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parts_amendment');
    }
};
