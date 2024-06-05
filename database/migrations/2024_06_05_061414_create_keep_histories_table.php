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
        Schema::create('keep_histories', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->string('qty');
            $table->string('cm');
            $table->dateTime('keep_date');
            $table->dateTime('expired_from');
            $table->dateTime('expired_to');
            $table->dateTime('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keep_histories');
    }
};
