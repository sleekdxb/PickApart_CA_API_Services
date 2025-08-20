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
         Schema::create('customers', function (Blueprint $table) {
            // Auto-increment primary key (id) of type INTEGER
            $table->id(); // this creates an auto-incrementing `id` column (BIGINT)

            // acc_id: Hashed account ID, linking to the Account table
            $table->string('acc_id', 255); // Assuming acc_id is a hashed value (string of length 255)
            $table->string('cus_id', 255)->unique();
            // address: Store the customer's address
            $table->string('address'); // Store customer address as a string

            // location: Store customer delivery location as a text
            $table->text('location'); // Store the delivery location as a text field

            // Timestamps: created_at and updated_at
            $table->timestamps();

            // Define a foreign key relationship with the `accounts` table
            // Assumes the `accounts` table has a column `acc_id`
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade'); 

            // Optional: Indexing the `acc_id` column for faster querying
            $table->index('acc_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
