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
            $table->json('table_id');
            $table->string('receipt_no');
            $table->dateTime('receipt_start_date');
            $table->dateTime('receipt_end_date');
            $table->decimal('total_amount', 13, 2);
            $table->decimal('rounding', 5, 2);
            $table->decimal('sst_amount', 13, 2);
            $table->decimal('service_tax_amount', 13, 2);
            $table->unsignedBigInteger('discount_id')->nullable()->default(NULL);
            $table->decimal('discount_amount', 13, 2);
            $table->json('bill_discounts')->nullable()->default(NULL);
            $table->decimal('bill_discount_total', 13, 2)->default(0.00);
            $table->decimal('grand_total', 13, 2);
            $table->decimal('amount_paid', 13, 2);
            $table->decimal('change', 13, 2); 
            $table->decimal('point_earned', 13, 2);
            $table->string('pax');
            $table->string('status');
            $table->string('invoice_status')->default('pending');
            $table->string('submitted_uuid')->nullable();
            $table->string('submission_date')->nullable();
            $table->unsignedBigInteger('consolidated_parent_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable()->default(NULL);
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
