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
        Schema::create('bill_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('discount_type');
            $table->double('discount_rate');
            $table->dateTime('discount_from');
            $table->dateTime('discount_to');
            $table->string('available_on');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('criteria');
            $table->double('requirement');
            $table->boolean('is_stackable')->default(false);
            $table->string('conflict');
            $table->integer('customer_usage')->nullable();
            $table->string('customer_usage_renew')->nullable();
            $table->integer('total_usage')->nullable();
            $table->string('total_usage_renew')->nullable();
            $table->json('tier')->nullable();
            $table->json('payment_method')->nullable();
            $table->boolean('is_auto_applied')->default(true);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_discounts');
    }
};
