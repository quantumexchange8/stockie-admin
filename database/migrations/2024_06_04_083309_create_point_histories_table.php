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
        Schema::create('point_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('payment_id');
            $table->string('type');
            $table->string('point_type');
            $table->integer('qty');
            $table->integer('amount');
            $table->integer('old_balance');
            $table->integer('new_balance');
            $table->string('remark')->nullable()->default(NULL);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('handled_by');
            $table->dateTime('redemption_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_histories');
    }
};
