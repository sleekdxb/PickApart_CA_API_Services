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
         Schema::create('payment_cards', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (ID)
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID
            $table->string('card_token', 255); // `card_token` to store the tokenized card information
            $table->string('card_type', 50); // `card_type` to store the type of card (Visa, MasterCard, etc.)
            $table->string('expiry_date', 5); // `expiry_date` to store the last 4 digits of the card (MM/YY)
            $table->string('last_four', 5); 
            $table->timestamps(); // `created_at` and `updated_at` timestamps

            // Foreign key constraint: linking `acc_id` to the `accounts` table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
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
