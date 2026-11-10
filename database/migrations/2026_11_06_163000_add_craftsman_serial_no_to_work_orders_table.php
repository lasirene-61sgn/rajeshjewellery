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
            $table->unsignedInteger('craftsman_serial_no')->nullable()->after('reference_no')->index();
        });

        // Backfill craftsman_serial_no (1 to N) for existing orders grouped by craftsman
        $craftsmanIds = DB::table('work_orders')
            ->whereNotNull('craftsman_id')
            ->distinct()
            ->pluck('craftsman_id');

        foreach ($craftsmanIds as $craftsmanId) {
            $orders = DB::table('work_orders')
                ->where('craftsman_id', $craftsmanId)
                ->orderByRaw('CASE WHEN allocated_at IS NOT NULL THEN allocated_at ELSE created_at END ASC')
                ->orderBy('id', 'asc')
                ->get(['id']);

            $serial = 1;
            foreach ($orders as $order) {
                DB::table('work_orders')
                    ->where('id', $order->id)
                    ->update(['craftsman_serial_no' => $serial++]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex(['craftsman_serial_no']);
            $table->dropColumn('craftsman_serial_no');
        });
    }
};

