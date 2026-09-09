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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_no')->unique()->nullable();
            $table->string('reference_no')->nullable()->index(); // REF-2026-001

            // Design Code & Product Details
            $table->string('product_name');
            $table->string('design_code')->index(); // e.g. D-101
            $table->string('design_nickname')->nullable()->index(); // e.g. Nickname

            // Specifications from form
            $table->string('category')->index(); // Rings, Necklaces
            $table->string('subcategory')->nullable()->index(); // Bridal, Daily
            $table->string('unit_type')->default('Piece'); // Piece, Pair, Grams
            $table->unsignedInteger('quantity')->default(1);
            $table->string('screw_type')->nullable(); // None / N/A, Bombay, South
            $table->string('size')->nullable(); // e.g. 14
            $table->string('length')->nullable(); // e.g. 18 inch
            $table->boolean('rhodium_polish')->default(true); // Yes/No
            $table->string('hallmark_purity')->default('916 (22K)');
            $table->decimal('target_weight', 8, 3); // 6.500g
            $table->date('due_date')->index();
            $table->string('job_type')->nullable(); // Handmade / Casting
            $table->text('instructions')->nullable();
            $table->string('design_image')->nullable();

            // Allocation & Lifecycle State
            $table->foreignId('craftsman_id')->nullable()->constrained('craftsmen')->nullOnDelete();
            $table->enum('status', [
                'pending',       // Unassigned
                'allocated',     // Assigned to craftsman
                'in_process',    // Accepted by craftsman
                'for_approval',  // Finished by craftsman, waiting admin
                'returned',      // Admin marked damaged/rework
                'completed'      // Fully approved
            ])->default('pending')->index();

            // Dates & Timestamps
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Damage / Return Tracking
            $table->text('return_reason')->nullable();
            $table->string('return_image')->nullable();
            $table->date('return_due_date')->nullable();
            $table->unsignedInteger('return_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
