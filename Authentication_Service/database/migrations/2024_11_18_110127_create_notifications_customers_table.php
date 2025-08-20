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

         Schema::create('notifications_customers', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('acc_id', 255); // `acc_id` as string, to store the hashed value
            $table->string('type'); // Type of the notification
            $table->text('data'); // JSON or Text field to store notification data
            $table->boolean('read')->default(false); // Read/Unread status
            $table->timestamp('read_at')->nullable(); // Timestamp when the notification was read
            $table->timestamps(); // Created and Updated timestamps

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
        Schema::dropIfExists('notifications_customers');
    }
};
