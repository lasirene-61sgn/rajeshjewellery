<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of orders assigned to the logged-in craftsman.
     */
    public function index(Request $request)
    {
        $craftsmanId = Auth::guard('craftsman')->id();

        $baseQuery = Order::where('craftsman_id', $craftsmanId);

        $counts = [
            'all'          => (clone $baseQuery)->count(),
            'allocated'    => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_process'   => (clone $baseQuery)->whereIn('status', ['in_process', 'in_progress'])->count(),
            'for_approval' => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'completed'    => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $query = (clone $baseQuery);

        // Filter by status tab if requested (e.g., allocated, in_process, for_approval, completed)
        if ($request->has('status') && !empty($request->status)) {
            if (in_array($request->status, ['in_process', 'in_progress'])) {
                $query->whereIn('status', ['in_process', 'in_progress']);
            } else {
                $query->where('status', $request->status);
            }
        }

        $orders = $query->latest()->paginate(10);

        return view('craftsman.orders.index', compact('orders', 'counts'));
    }

    /**
     * Display the specified order details.
     */
    public function show(Order $order)
    {
        // Ensure the craftsman is only viewing their own assigned orders
        if ((int) $order->craftsman_id !== (int) Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('craftsman.orders.show', compact('order'));
    }

    /**
     * Submit an order for admin approval.
     */
    public function submitForApproval(Order $order)
    {
        if ((int) $order->craftsman_id !== (int) Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $craftsmanName = Auth::guard('craftsman')->user()?->name ?? 'Craftsman';

        $order->update([
            'status' => 'for_approval'
        ]);

        $order->logActivity(
            action: 'for_approval',
            note: "Order submitted for approval by Craftsman {$craftsmanName}.",
            details: [
                'status'         => 'for_approval',
                'craftsman_name' => $craftsmanName,
            ]
        );

        return back()->with('success', "Order #{$order->reference_number} submitted for approval successfully.");
    }

    /**
     * Bulk Accept allocated orders by Craftsman.
     */
    public function bulkAccept(Request $request)
    {
        $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $orders = Order::whereIn('id', $request->order_ids)
            ->where('craftsman_id', $craftsman->id)
            ->where('status', 'allocated')
            ->get();

        foreach ($orders as $order) {
            $order->update(['status' => 'in_process']);
            $order->logActivity(
                action: 'in_process',
                note: "Order accepted via bulk action by Craftsman {$craftsman->name}.",
                details: ['craftsman_name' => $craftsman->name]
            );
        }

        return back()->with('success', count($orders) . ' orders accepted successfully.');
    }

    /**
     * Bulk Complete orders and submit for admin approval.
     */
    public function bulkComplete(Request $request)
    {
        $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $orders = Order::whereIn('id', $request->order_ids)
            ->where('craftsman_id', $craftsman->id)
            ->whereIn('status', ['allocated', 'in_process', 'in_progress', 'returned'])
            ->get();

        foreach ($orders as $order) {
            $order->update(['status' => 'for_approval']);
            $order->logActivity(
                action: 'for_approval',
                note: "Submitted in bulk by Craftsman {$craftsman->name} for Admin approval.",
                details: ['craftsman_name' => $craftsman->name]
            );
        }

        return back()->with('success', count($orders) . ' orders submitted for approval successfully.');
    }

    /**
     * Bulk Reject allocated orders by Craftsman.
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'order_ids'     => ['required', 'array', 'min:1'],
            'order_ids.*'   => ['exists:orders,id'],
            'reject_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $orders = Order::whereIn('id', $request->order_ids)
            ->where('craftsman_id', $craftsman->id)
            ->where('status', 'allocated')
            ->get();

        $reason = $request->reject_reason ?? 'Rejected by Craftsman via bulk action';

        foreach ($orders as $order) {
            $order->update(['status' => 'returned']);
            $order->logActivity(
                action: 'returned',
                note: "Order rejected via bulk action by Craftsman {$craftsman->name}. Reason: {$reason}",
                details: [
                    'reject_reason'  => $reason,
                    'craftsman_name' => $craftsman->name,
                ]
            );
        }

        return back()->with('success', count($orders) . ' orders rejected.');
    }

    /**
     * Update the status of the order (e.g., In Progress, Completed / For Approval).
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Ensure the craftsman owns this order
        if ((int) $order->craftsman_id !== (int) Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'status' => ['required', 'in:in_progress,in_process,for_approval,completed']
        ]);

        $craftsmanName = Auth::guard('craftsman')->user()?->name ?? 'Craftsman';

        // When completing an order from allocated or in process, mark as 'for_approval' for admin approval
        if (in_array($request->status, ['completed', 'for_approval'])) {
            return $this->submitForApproval($order);
        }

        $order->update([
            'status' => 'in_process'
        ]);

        $order->logActivity(
            action: 'in_process',
            note: "Order marked as In Process by Craftsman {$craftsmanName}.",
            details: [
                'status'         => 'in_process',
                'craftsman_name' => $craftsmanName,
            ]
        );

        return back()->with('success', 'Order status updated to In Process.');
    }
}
