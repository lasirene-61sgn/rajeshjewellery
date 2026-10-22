<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Craftsman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('craftsman');

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10);
        $craftsmen = Craftsman::all();

        $counts = [
            'all'        => Order::count(),
            'pending'    => Order::where('status', 'pending')->count(),
            'allocated'  => Order::where('status', 'allocated')->count(),
            'in_process' => Order::where('status', 'in_process')->count(),
            'returned'   => Order::where('status', 'returned')->count(),
            'completed'  => Order::where('status', 'completed')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'craftsmen', 'counts'));
    }

    public function create()
    {
        $craftsmen = Craftsman::all(); 
        return view('admin.orders.create', compact('craftsmen'));
    }

    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('orders', 'public');
        }

        // If craftsman is chosen during creation, set status to 'allocated'
        if (!empty($data['craftsman_id'])) {
            $data['status'] = 'allocated';
        } else {
            // Otherwise default to pending and ensure craftsman_id is null
            $data['status'] = 'pending';
            $data['craftsman_id'] = null;
        }

        Order::create($data);

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $craftsmen = Craftsman::all();
        return view('admin.orders.edit', compact('order', 'craftsmen'));
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($order->image) {
                Storage::disk('public')->delete($order->image);
            }
            $data['image'] = $request->file('image')->store('orders', 'public');
        }

        // Handle allocation status changes correctly
        if (!empty($data['craftsman_id'])) {
            $data['status'] = 'allocated';
        } else {
            $data['craftsman_id'] = null;
            $data['status'] = 'pending';
        }

        $order->update($data);

        return redirect()->route('admin.orders.index')->with('success', 'Order updated successfully.');
    }

    public function allocate(Request $request, Order $order)
    {
        $request->validate([
            'craftsman_id' => ['required', 'exists:craftsmen,id']
        ]);

        $order->update([
            'craftsman_id' => $request->craftsman_id,
            'status' => 'allocated' // Forces status to allocated when assigned
        ]);

        return back()->with('success', 'Order allocated to craftsman successfully.');
    }

    public function forceComplete(Order $order)
    {
        $order->update([
            'status' => 'completed'
        ]);

        return back()->with('success', 'Order has been marked as completed by Admin.');
    }

    public function destroy(Order $order)
    {
        if ($order->image) {
            Storage::disk('public')->delete($order->image);
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }
}