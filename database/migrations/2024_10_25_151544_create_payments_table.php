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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id')->nullable()->default(NULL);
            $table->unsignedBigInteger('order_id');
            $table->string('receipt_no');
            $table->dateTime('receipt_start_date');
            $table->dateTime('receipt_end_date');
            $table->decimal('total_amount', 13, 2);
            $table->decimal('grand_total', 13, 2);
            $table->decimal('rounding', 5, 2);
            $table->decimal('sst_amount', 13, 2);
            $table->decimal('service_tax_amount', 13, 2);
            $table->unsignedBigInteger('discount_id');
            $table->decimal('discount_amount', 13, 2);
            $table->integer('points_earned');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('handled_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
