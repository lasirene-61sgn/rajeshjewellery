<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the craftsman portal dashboard overview.
     */
    public function index(): View
    {
        $craftsmanId = Auth::guard('craftsman')->id();
        $today = Carbon::today()->toDateString();

        // Scoped work orders base query
        $ordersQuery = WorkOrder::where('craftsman_id', $craftsmanId);

        $counts = [
            // Today Stats
            'today_allocated' => (clone $ordersQuery)->where('status', 'allocated')->whereDate('updated_at', Carbon::today())->count(),
            'today_in_process'=> (clone $ordersQuery)->where('status', 'in_process')->whereDate('updated_at', Carbon::today())->count(),
            'today_completed' => (clone $ordersQuery)->where('status', 'completed')->whereDate('updated_at', Carbon::today())->count(),

            // Overall Stats
            'overall_new'     => (clone $ordersQuery)->whereIn('status', ['pending', 'allocated'])->count(),
            'overall_rejected'=> (clone $ordersQuery)->where('status', 'returned')->count(),
            'overall_overdue' => (clone $ordersQuery)->where('status', '!=', 'completed')
                                ->where(function ($q) use ($today) {
                                    $q->where(function ($sub) use ($today) {
                                        $sub->whereNotNull('return_due_date')
                                            ->whereDate('return_due_date', '<', $today);
                                    })->orWhere(function ($sub) use ($today) {
                                        $sub->whereNull('return_due_date')
                                            ->whereDate('due_date', '<', $today);
                                    });
                                })->count(),
        ];

        // Recent active orders for the dashboard widget
        $recentOrders = (clone $ordersQuery)
            ->whereIn('status', ['allocated', 'in_process', 'returned'])
            ->latest()
            ->take(5)
            ->get();

        // Assigned Design Codes
        $craftsman = Auth::guard('craftsman')->user();
        $assignedDesigns = $craftsman->designCodes()->orderBy('code')->get();

        return view('craftsman.dashboard', compact('counts', 'recentOrders', 'assignedDesigns'));
    }
}