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
        Schema::create('order_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_id');
            $table->string('reservation');
            $table->string('pax');
            $table->string('waiter_id');
            $table->string('status');
            $table->dateTime('reservation_date');
            $table->unsignedInteger('order_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tables');
    }
};
