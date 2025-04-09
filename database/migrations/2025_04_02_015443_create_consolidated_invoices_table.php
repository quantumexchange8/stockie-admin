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
        Schema::create('consolidated_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('c_invoice_no');
            $table->dateTime('c_datetime');
            $table->string('docs_type');
            $table->double('c_total_amount')->nullable();
            $table->dateTime('c_period_start')->nullable();
            $table->dateTime('c_period_end')->nullable();
            $table->string('status')->nullable();
            $table->string('submitted_uuid')->nullable();
            $table->string('uuid')->nullable();
            $table->longText('remark')->nullable();
            $table->dateTime('cancel_expired_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidated_invoices');
    }
};
