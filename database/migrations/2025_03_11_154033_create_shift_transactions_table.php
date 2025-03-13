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
        Schema::create('shift_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opened_by');
            $table->unsignedBigInteger('closed_by')->nullable()->default(NULL);
            $table->string('shift_no');
            $table->decimal('starting_cash', 13, 2)->default(0.00);
            $table->decimal('paid_in', 13, 2)->default(0.00);
            $table->decimal('paid_out', 13, 2)->default(0.00);
            $table->decimal('cash_refund', 13, 2)->default(0.00);
            $table->decimal('expected_cash', 13, 2)->default(0.00);
            $table->decimal('cash_sales', 13, 2)->default(0.00);
            $table->decimal('card_sales', 13, 2)->default(0.00);
            $table->decimal('ewallet_sales', 13, 2)->default(0.00);
            $table->decimal('gross_sales', 13, 2)->default(0.00);
            $table->decimal('sst_amount', 13, 2)->default(0.00);
            $table->decimal('service_tax_amount', 13, 2)->default(0.00);
            $table->decimal('total_refund', 13, 2)->default(0.00);
            $table->decimal('total_void', 13, 2)->default(0.00);
            $table->decimal('total_discount', 13, 2)->default(0.00);
            $table->decimal('net_sales', 13, 2)->default(0.00);
            $table->decimal('closing_cash', 13, 2)->nullable()->default(NULL);
            $table->decimal('difference', 13, 2)->nullable()->default(NULL);
            $table->dateTime('shift_opened')->nullable()->default(NULL);
            $table->dateTime('shift_closed')->nullable()->default(NULL);
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
        Schema::dropIfExists('shift_transactions');
    }
};
