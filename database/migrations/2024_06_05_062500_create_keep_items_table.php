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
        Schema::create('keep_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_item_subitem_id');
            $table->double('qty')->nullable();
            $table->decimal('cm', 13, 2)->nullable();
            $table->string('remark')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status');
            $table->dateTime('expired_from');
            $table->dateTime('expired_to');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keep_items');
    }
};
