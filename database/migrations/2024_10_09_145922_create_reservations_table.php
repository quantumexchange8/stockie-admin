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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_no');
            $table->unsignedBigInteger('customer_id')->nullable()->default(NULL);
            $table->string('name');
            $table->string('pax');
            $table->json('table_no');
            $table->string('phone');
            $table->string('cancel_type');
            $table->string('remark');
            $table->string('status');
            $table->dateTime('reservation_date');
            $table->dateTime('action_date')->nullable()->default(NULL);
            $table->unsignedBigInteger('order_id')->nullable()->default(NULL);
            $table->unsignedBigInteger('handled_by');
            $table->unsignedBigInteger('reserved_by');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
