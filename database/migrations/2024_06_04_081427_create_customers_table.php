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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->nullable()->default(NULL);
            $table->string('full_name');
            $table->string('email')->unique()->nullable()->default(NULL);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('dial_code')->nullable()->default(NULL);
            $table->string('phone')->unique()->nullable()->default(NULL);
            $table->string('password');
            $table->string('ranking')->nullable()->default(NULL);
            $table->string('role')->nullable()->default(NULL);
            $table->decimal('point', 13, 2);
            $table->decimal('total_spending', 13, 2)->default(0.00);
            $table->string('profile_photo')->nullable()->default(NULL);
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->string('first_login')->nullable()->default(NULL);
            $table->string('remark')->nullable()->default(NULL);
            $table->string('status')->nullable()->default(NULL);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
