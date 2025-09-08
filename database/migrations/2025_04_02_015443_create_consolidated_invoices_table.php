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
            $table->double('c_amount')->nullable()->default(0);
            $table->double('c_total_amount')->nullable()->default(0);
            $table->dateTime('c_period_start')->nullable()->default(NULL);
            $table->dateTime('c_period_end')->nullable()->default(NULL);
            $table->string('status')->nullable()->default(NULL);
            $table->string('submitted_uuid')->nullable()->default(NULL);
            $table->string('uuid')->nullable()->default(NULL);
            $table->string('longId')->nullable()->default(NULL);
            $table->string('internal_id')->nullable()->default(NULL);
            $table->dateTime('submitted_at')->nullable()->default(NULL);
            $table->dateTime('invoice_datetime')->nullable()->default(NULL);
            $table->dateTime('rejected_at')->nullable()->default(NULL);
            $table->longText('remark')->nullable()->default(NULL);
            $table->dateTime('cancel_expired_at')->nullable()->default(NULL);
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
