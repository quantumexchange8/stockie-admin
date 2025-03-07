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
        Schema::create('waiter_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('waiter_id')->nullable();
            $table->string('shift_id')->nullable();
            $table->string('week_range')->nullable();
            $table->integer('weeks')->nullable();
            $table->string('days')->nullable();
            $table->date('date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiter_shifts');
    }
};
