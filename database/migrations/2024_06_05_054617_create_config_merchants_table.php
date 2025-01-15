<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('config_merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('merchant_name');
            $table->string('tin_no');
            $table->string('registration_no');
            $table->string('msic_code');
            $table->string('merchant_contact');
            $table->string('email_address');
            $table->string('sst_registration_no');
            $table->string('description');
            $table->unsignedBigInteger('classification_code');
            $table->string('merchant_address_line1');
            $table->string('merchant_address_line2');
            $table->string('postal_code');
            $table->string('area');
            $table->string('state');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_merchants');
    }
};
