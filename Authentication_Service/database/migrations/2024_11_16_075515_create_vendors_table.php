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
        Schema::create('vendors', function (Blueprint $table) {
    // Auto-increment primary key (id) of type BIGINT
    $table->id(); // This will create an auto-incrementing `id` column (BIGINT)

    // acc_id: Hashed account ID, linking to the Account table
    $table->string('acc_id', 255); // Assuming acc_id is a hashed value (string of length 255)
    $table->string('vend_id', 255)->unique(); // Ensure this length matches with `subvendors`

    // address: Store the vendor's address
    $table->string('address'); // Address as a string

    // business_name: Store the vendor's business name
    $table->string('business_name'); // Business name as a string

    // map_location: Store the business location (map coordinates or address)
    $table->text('map_location'); // Location as a text field (could be map coordinates, etc.)

    // official_email: Vendor's official email (nullable)
    $table->string('official_email')->nullable(); // Nullable email field

    // official_phone: Vendor's official phone (nullable)
    $table->string('official_phone')->nullable(); // Nullable phone number field

    // primary_contact_name: Primary contact name (nullable)
    $table->string('primary_contact_name')->nullable(); // Nullable primary contact name field

    // trade_license_expiry_date: License expiry date (nullable)
    $table->date('trade_license_expiry_date')->nullable(); // Nullable date field

    // trade_license_number: Vendor's trade license number (nullable)
    $table->string('trade_license_number')->nullable(); // Nullable trade license number

    // Timestamps for created_at and updated_at
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
        Schema::dropIfExists('vendors');
    }
};
