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
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (ID)
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID
            $table->string('transaction_id', 255)->unique(); // `transaction_id` to store the hashed transaction ID
            $table->decimal('amount', 15, 2); // `amount` to store the paid amount
            $table->string('currency', 3); // `currency` to store the currency code (e.g., 'USD')
            $table->string('payment_id', 255); // `payment_id` to store the payment ID
            $table->string('status', 50); // `status` to store the payment state (e.g., 'success', 'failed')
            $table->string('last_four', 5); // `last_four` to store the last 4 digits of the card number
            $table->unsignedBigInteger('card_id'); // Foreign key to the payment_cards table
            $table->unsignedBigInteger('otp_id')->nullable(); // Foreign key to the otps table (nullable if not always provided)
            $table->timestamps(); // `created_at` and `updated_at` timestamps

            // Foreign key constraint: linking `acc_id` to the `accounts` table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');

            // Foreign key constraint: linking `card_id` to the `payment_cards` table
            $table->foreign('card_id')->references('id')->on('payment_cards')->onDelete('cascade');

            // Foreign key constraint: linking `otp_id` to the `otps` table
            $table->foreign('otp_id')->references('id')->on('otps')->onDelete('set null'); // Set null if OTP is deleted
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
