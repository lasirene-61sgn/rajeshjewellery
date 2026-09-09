<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();
        $today = \Carbon\Carbon::today();

        $counts = [
            // Today Stats
            'today_created'      => \App\Models\WorkOrder::whereDate('created_at', $today)->count(),
            'today_allocated'    => \App\Models\WorkOrder::where('status', 'allocated')->whereDate('updated_at', $today)->count(),
            'today_in_process'   => \App\Models\WorkOrder::where('status', 'in_process')->whereDate('updated_at', $today)->count(),
            'today_for_approval' => \App\Models\WorkOrder::where('status', 'for_approval')->whereDate('updated_at', $today)->count(),
            'today_completed'    => \App\Models\WorkOrder::where('status', 'completed')->whereDate('updated_at', $today)->count(),
            
            // Overall Stats
            'overall_new'        => \App\Models\WorkOrder::whereIn('status', ['pending', 'allocated'])->count(),
            'overall_rejected'   => \App\Models\WorkOrder::where('status', 'returned')->count(),
            'overall_overdue'    => \App\Models\WorkOrder::where('status', '!=', 'completed')
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

        return view('admin.dashboard', [
            'admin' => $admin,
            'counts' => $counts,
        ]);
    }
}