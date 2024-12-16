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
        Schema::create('ranking_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ranking_id');
            $table->string('reward_type');
            $table->string('min_purchase');
            $table->string('discount')->nullable()->default(NULL);
            $table->bigInteger('min_purchase_amount')->nullable()->default(NULL);
            $table->string('bonus_point')->nullable()->default(NULL);
            $table->unsignedBigInteger('free_item')->nullable()->default(NULL);
            $table->string('item_qty')->nullable()->default(NULL);
            $table->dateTime('valid_period_from')->nullable()->default(NULL);
            $table->dateTime('valid_period_to')->nullable()->default(NULL);
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
        Schema::dropIfExists('ranking_rewards');
    }
};
