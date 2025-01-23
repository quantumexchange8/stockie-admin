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
        Schema::create('bill_discount_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_discount_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('customer_usage')->nullable();
            $table->unsignedBigInteger('total_usage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_discount_usages');
    }
};
