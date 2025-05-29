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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('table_no');
            $table->double('seat');
            $table->unsignedBigInteger('zone_id');
            $table->string('status');
            $table->unsignedBigInteger('order_id')->nullable()->default(NULL);
            $table->string('state')->default('active');
            $table->boolean('is_locked');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
