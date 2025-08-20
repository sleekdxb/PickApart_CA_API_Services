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
         Schema::create('memberships', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (ID)
            $table->string('transaction_id', 255)->unique(); // `transaction_id` to store the hashed transaction ID (foreign key to payments table)
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID (foreign key to accounts table)
            $table->date('start_date'); // `start_date` to store when the membership started
            $table->date('end_date'); // `end_date` to store when the membership ends
            $table->string('type'); // `type` to store the membership type (e.g., 'premium', 'basic', etc.)
            $table->char('status', 1); // `status` to store the membership status (e.g., 'A' = Active, 'I' = Inactive)
            $table->timestamps(); // `created_at` and `updated_at` timestamps

            // Foreign key constraint: linking `transaction_id` to the `payments` table
           

            // Foreign key constraint: linking `acc_id` to the `accounts` table
            $table->foreign('acc_id')->references('acc_id')->on('accounts')->onDelete('cascade');
             $table->foreign('transaction_id')->references('transaction_id')->on('payments')->onDelete('cascade');
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
