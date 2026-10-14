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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('craftsman_id')->nullable()->constrained('craftsmen')->nullOnDelete();
            $table->string('image')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('product_name')->nullable();
            $table->string('size')->nullable();
            $table->string('length')->nullable();
            $table->string('weight')->nullable();
            $table->string('quantity')->nullable();
            $table->string('order_type')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->date('created_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
