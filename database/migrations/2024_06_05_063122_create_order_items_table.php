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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable()->default(NULL);
            $table->string('type');
            $table->unsignedBigInteger('keep_item_id')->nullable()->default(NULL);
            $table->unsignedBigInteger('product_id');
            $table->double('item_qty');
            $table->double('amount_before_discount');
            $table->unsignedBigInteger('discount_id')->nullable()->default(NULL);
            $table->double('discount_amount');
            $table->double('amount');
            $table->integer('point_earned')->nullable()->default(0); // unused
            $table->integer('point_redeemed')->nullable()->default(0); // unused
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
        Schema::dropIfExists('order_items');
    }
};
