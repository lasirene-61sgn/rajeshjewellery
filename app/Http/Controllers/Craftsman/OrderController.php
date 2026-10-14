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

        $query = Order::where('craftsman_id', $craftsmanId);

        // Filter by status tab if requested (e.g., allocated, in_progress, completed)
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        return view('craftsman.orders.index', compact('orders'));
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
     * Update the status of the order (e.g., In Progress, Completed).
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Ensure the craftsman owns this order
        if ((int) $order->craftsman_id !== (int) Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'status' => ['required', 'in:in_progress,completed']
        ]);

        $order->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Order status updated successfully.');
    }
}
