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
        Schema::create('employee_incentives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('incentive_id');
            $table->string('type');
            $table->string('rate');
            $table->decimal('amount', 13, 2);
            $table->decimal('sales_target', 13, 2);
            $table->string('recurring_on');
            $table->dateTime('effective_date');
            $table->dateTime('period_start');
            $table->dateTime('period_end');
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
        Schema::dropIfExists('employee_incentives');
    }
};
