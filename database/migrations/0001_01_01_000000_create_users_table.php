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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('full_name')->nullable()->default(NULL);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable()->default(NULL);
            $table->string('worker_email')->unique()->nullable()->default(NULL);
            $table->string('phone')->unique()->nullable()->default(NULL);
            $table->string('password');
            $table->string('position')->nullable()->default(NULL);
            $table->unsignedBigInteger('position_id')->nullable()->default(NULL);
            $table->string('role_id')->nullable()->default(NULL);
            $table->integer('passcode')->nullable()->default(NULL);
            $table->string('passcode_status')->nullable()->default(NULL);
            $table->string('profile_photo')->nullable()->default(NULL);
            $table->string('employment_type');
            $table->string('salary')->nullable()->default(NULL);
            $table->string('status')->default('Active');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
