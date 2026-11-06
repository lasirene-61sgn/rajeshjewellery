<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\Order;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Unified Dashboard API.
     * Automatically returns Admin overview or Craftsman overview based on the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            return $this->adminDashboard($user);
        }

        if ($user instanceof Craftsman) {
            return $this->craftsmanDashboard($user);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized role.',
        ], 403);
    }

    /**
     * Admin Dashboard Data
     */
    protected function adminDashboard(Admin $admin): JsonResponse
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();

        $workOrderCounts = [
            'today_created'      => WorkOrder::whereDate('created_at', $today)->count(),
            'today_allocated'    => WorkOrder::where('status', 'allocated')->whereDate('updated_at', $today)->count(),
            'today_in_process'   => WorkOrder::where('status', 'in_process')->whereDate('updated_at', $today)->count(),
            'today_for_approval' => WorkOrder::where('status', 'for_approval')->whereDate('updated_at', $today)->count(),
            'today_completed'    => WorkOrder::where('status', 'completed')->whereDate('updated_at', $today)->count(),

            'overall_total'      => WorkOrder::count(),
            'overall_new'        => WorkOrder::whereIn('status', ['pending', 'allocated'])->count(),
            'overall_rejected'   => WorkOrder::where('status', 'returned')->count(),
            'overall_overdue'    => WorkOrder::where('status', '!=', 'completed')
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

        $customerOrderCounts = [
            'total'       => Order::count(),
            'pending'     => Order::where('status', 'pending')->count(),
            'allocated'   => Order::where('status', 'allocated')->count(),
            'in_process'  => Order::where('status', 'in_process')->count(),
            'returned'    => Order::where('status', 'returned')->count(),
            'completed'   => Order::where('status', 'completed')->count(),
        ];

        $craftsmenSummary = [
            'total'    => Craftsman::count(),
            'active'   => Craftsman::where('is_active', true)->count(),
            'inactive' => Craftsman::where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => [
                'work_orders'     => $workOrderCounts,
                'customer_orders' => $customerOrderCounts,
                'craftsmen'       => $craftsmenSummary,
                'server_time'     => Carbon::now('Asia/Kolkata')->toIso8601String(),
            ],
        ]);
    }

    /**
     * Craftsman Dashboard Data
     */
    protected function craftsmanDashboard(Craftsman $craftsman): JsonResponse
    {
        $craftsmanId = $craftsman->id;
        $today = Carbon::today('Asia/Kolkata')->toDateString();

        $ordersQuery = WorkOrder::where('craftsman_id', $craftsmanId);

        $counts = [
            'today_allocated'   => (clone $ordersQuery)->where('status', 'allocated')->whereDate('updated_at', $today)->count(),
            'today_in_process'  => (clone $ordersQuery)->where('status', 'in_process')->whereDate('updated_at', $today)->count(),
            'today_completed'   => (clone $ordersQuery)->where('status', 'completed')->whereDate('updated_at', $today)->count(),

            'overall_new'       => (clone $ordersQuery)->whereIn('status', ['pending', 'allocated'])->count(),
            'overall_rejected'  => (clone $ordersQuery)->where('status', 'returned')->count(),
            'overall_completed' => (clone $ordersQuery)->where('status', 'for_approval')->count(),
            'overall_overdue'   => (clone $ordersQuery)->where('status', '!=', 'completed')
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

        $recentOrders = (clone $ordersQuery)
            ->whereIn('status', ['allocated', 'in_process', 'returned', 'for_approval'])
            ->latest('id')
            ->take(5)
            ->get()
            ->map(fn (WorkOrder $wo) => [
                'id'            => $wo->id,
                'work_order_no' => $wo->work_order_no,
                'reference_no'  => $wo->reference_no,
                'product_name'  => $wo->product_name,
                'design_code'   => $wo->design_code,
                'status'        => $wo->status,
                'has_seal'      => (bool) $wo->has_seal,
                'target_weight' => (float) $wo->target_weight,
                'image_url'     => $wo->design_image ? url('storage/' . $wo->design_image) : null,
                'due_date'      => $wo->return_due_date ? $wo->return_due_date->format('d M') : ($wo->due_date ? $wo->due_date->format('d M') : null),
            ]);

        $assignedDesigns = $craftsman->designCodes()->orderBy('code')->get()->map(fn ($d) => [
            'id'        => $d->id,
            'code'      => $d->code,
            'nickname'  => $d->nickname,
            'image_url' => $d->image ? url('storage/' . $d->image) : null,
        ]);

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => [
                'counts'           => $counts,
                'recent_orders'    => $recentOrders,
                'assigned_designs' => $assignedDesigns,
                'server_time'      => Carbon::now('Asia/Kolkata')->toIso8601String(),
            ],
        ]);
    }
}

