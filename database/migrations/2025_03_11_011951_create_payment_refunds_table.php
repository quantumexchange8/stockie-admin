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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('refund_no');
            $table->string('subtotal_refund_amount')->default(0);
            $table->string('refund_sst')->default(0);
            $table->string('refund_service_tax')->default(0);
            $table->string('refund_rounding')->default(0);
            $table->double('total_refund_amount')->default(0);
            $table->double('refund_point')->default(0);
            $table->string('refund_method');
            $table->string('others_remark')->nullable();
            $table->string('refund_remark')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
