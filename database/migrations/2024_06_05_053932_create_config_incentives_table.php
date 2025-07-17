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
        Schema::create('config_incentives', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('rate');
            $table->dateTime('effective_date');
            $table->string('recurring_on');
            $table->decimal('monthly_sale', 13, 2)->default(0.00);
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
        Schema::dropIfExists('config_incentives');
    }
};
