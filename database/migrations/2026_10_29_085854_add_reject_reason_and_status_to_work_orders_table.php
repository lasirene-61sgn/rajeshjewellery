<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->text('reject_reason')->nullable()->after('return_count');
        });

        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status ENUM('pending','allocated','in_process','partially_completed','for_approval','returned','completed','rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE work_orders MODIFY COLUMN status ENUM('pending','allocated','in_process','partially_completed','for_approval','returned','completed') NOT NULL DEFAULT 'pending'");

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });
    }
};
