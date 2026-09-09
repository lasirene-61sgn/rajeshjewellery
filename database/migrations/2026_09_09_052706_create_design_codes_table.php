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
        Schema::create('design_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('nickname')->nullable();
            $table->timestamps();
        });

        Schema::create('craftsman_design_code', function (Blueprint $table){
            $table->id();
            $table->foreignId('craftsman_id')->constrained('craftsmen')->cascadeOnDelete();
            $table->foreignId('design_code_id')->constrained('design_codes')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_codes');
        Schema::dropIfExists('craftsman_design_code');
    }
};
