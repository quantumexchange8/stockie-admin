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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('price');
            $table->string('bucket');
            $table->boolean('is_redeemable');
            $table->string('point');
            $table->unsignedInteger('category_id');
            $table->unsignedBigInteger('discount_id');
            // $table->string('keep');
            $table->string('status');
            $table->string('availability');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
