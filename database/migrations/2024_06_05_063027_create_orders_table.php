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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->string('pax');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable()->default(NULL);
            $table->decimal('amount', 13, 2)->nullable()->default(NULL);
            $table->mediumInteger('voucher_id')->nullable()->default(NULL);
            $table->decimal('total_amount', 13, 2);
            $table->decimal('discount_amount', 13, 2)->nullable()->default(NULL);
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
        Schema::dropIfExists('orders');
    }
};
