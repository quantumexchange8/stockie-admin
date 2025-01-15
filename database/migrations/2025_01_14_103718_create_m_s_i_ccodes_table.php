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
        Schema::create('m_s_i_ccodes', function (Blueprint $table) {
            $table->id();
            $table->string('Code');
            $table->string('Description');
            $table->string('MSIC Category Reference');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_s_i_ccodes');
    }
};
