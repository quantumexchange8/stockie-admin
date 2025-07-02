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
            $table->decimal('amount', 13, 2);
            $table->decimal('old_balance', 13, 2);
            $table->decimal('new_balance', 13, 2);
            $table->decimal('expire_balance', 13, 2)->nullable()->default(0.00);
            $table->dateTime('expired_at')->nullable()->default(NULL);
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
