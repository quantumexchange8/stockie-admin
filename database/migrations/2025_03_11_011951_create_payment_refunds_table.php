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
            $table->string('customer_id')->default(NULL);
            $table->string('refund_no');
            $table->double('subtotal_refund_amount')->default(0);
            $table->double('refund_sst')->default(0);
            $table->double('refund_service_tax')->default(0);
            $table->double('refund_rounding')->default(0);
            $table->double('total_refund_amount')->default(0);
            $table->decimal('refund_point', 13, 2)->default(0.00);
            $table->string('refund_method');
            $table->string('others_remark')->nullable()->default(NULL);
            $table->string('refund_remark')->nullable()->default(NULL);
            $table->string('status')->nullable()->default(NULL);
            $table->string('invoice_status')->nullable()->default(NULL);
            $table->bigInteger('consolidated_parent_id')->nullable()->default(NULL);
            $table->string('submitted_uuid')->nullable()->default(NULL);
            $table->string('uuid')->nullable()->default(NULL);
            $table->dateTime('submission_date')->nullable()->default(NULL);
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
