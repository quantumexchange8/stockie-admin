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
            $table->unsignedBigInteger('keep_item_id');
            $table->unsignedBigInteger('order_item_id')->nullable()->default(NULL);
            $table->string('qty');
            $table->string('cm');
            $table->dateTime('keep_date');
            $table->integer('kept_balance')->nullable()->default(0);
            $table->unsignedBigInteger('user_id');
            $table->string('kept_from_table');
            $table->string('redeemed_to_table')->nullable();
            $table->string('remark')->nullable();
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
        Schema::dropIfExists('keep_histories');
    }
};
