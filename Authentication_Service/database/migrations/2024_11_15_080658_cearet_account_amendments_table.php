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
         Schema::create('account_amendments', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key for the amendment record (ID)
            $table->string('acc_id', 255); // `acc_id` to store the hashed account ID
            $table->string('change_type', 255); // `change_type` to store the type of change applied
            $table->text('changed_data'); // `changed_data` to store the data that was changed
            $table->unsignedBigInteger('record_id'); // `record_id` to store the ID of the changed record
            $table->text('table_name'); // `table_name` to store the name of the table where the change occurred
            $table->string('acc_agent_id', 255); // `acc_agent_id` to store the account ID of the agent who made the change
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
