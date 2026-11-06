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
        Schema::create('work_order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->string('action')->index(); // created, allocated, in_process, overdue, for_approval, partially_completed, completed, returned, undo_allocation, undo_completion, rejected
            $table->string('status')->nullable()->index();
            $table->unsignedInteger('attempt_number')->nullable(); // For partially completed counter, return counter, etc.
            $table->string('actor_type')->nullable(); // admin, craftsman, system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->text('note')->nullable();
            $table->json('details')->nullable(); // e.g. completed_quantity, balance_quantity, craftsman_name, return_reason, etc.
            $table->timestamps();

            $table->index(['work_order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_logs');
    }
};
