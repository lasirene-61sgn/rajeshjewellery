<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Craftsman;
use App\Models\Order;
use Carbon\Carbon;
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

        if (!empty($data['craftsman_id'])) {
            $data['status'] = 'allocated';
        } else {
            $data['status'] = 'pending';
            $data['craftsman_id'] = null;
        }

        $order = Order::create($data);

        $order->logActivity(
            action: 'created',
            note: "Order #{$order->reference_number} created with quantity {$order->quantity}.",
            details: [
                'reference_number' => $order->reference_number,
                'product_name'     => $order->product_name,
                'quantity'         => $order->quantity,
                'due_date'         => optional($order->due_date)->format('Y-m-d'),
            ]
        );

        if (!empty($data['craftsman_id'])) {
            $craftsman = Craftsman::find($data['craftsman_id']);
            $order->logActivity(
                action: 'allocated',
                note: "Order allocated to Craftsman: {$craftsman?->name}",
                details: [
                    'craftsman_id'   => $craftsman?->id,
                    'craftsman_name' => $craftsman?->name,
                ]
            );
        }

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }

    public function show(Order $order)
    {
        // Check overdue if due_date is past and not completed
        if ($order->status !== 'completed' && $order->due_date && Carbon::parse($order->due_date)->lt(Carbon::today('Asia/Kolkata'))) {
            $dueDateStr = Carbon::parse($order->due_date)->format('Y-m-d');
            $alreadyLogged = $order->logs()
                ->where('action', 'overdue')
                ->where('details->due_date', $dueDateStr)
                ->exists();

            if (!$alreadyLogged) {
                $order->logActivity(
                    action: 'overdue',
                    note: "Order became overdue. (Due date was " . Carbon::parse($order->due_date)->format('d M Y') . ").",
                    details: ['due_date' => $dueDateStr],
                    actorType: 'system',
                    actorName: 'System'
                );
            }
        }

        $order->load(['craftsman', 'logs' => function ($q) {
            $q->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        }]);

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

        $oldCraftsmanId = $order->craftsman_id;

        if (!empty($data['craftsman_id'])) {
            $data['status'] = 'allocated';
        } else {
            $data['craftsman_id'] = null;
            $data['status'] = 'pending';
        }

        $order->update($data);

        if (!empty($data['craftsman_id']) && $data['craftsman_id'] != $oldCraftsmanId) {
            $craftsman = Craftsman::find($data['craftsman_id']);
            $order->logActivity(
                action: 'allocated',
                note: "Craftsman updated/allocated to: {$craftsman?->name}",
                details: [
                    'craftsman_id'   => $craftsman?->id,
                    'craftsman_name' => $craftsman?->name,
                ]
            );
        } else {
            $order->logActivity(
                action: 'updated',
                note: "Order details updated by Admin.",
            );
        }

        return redirect()->route('admin.orders.index')->with('success', 'Order updated successfully.');
    }

    public function allocate(Request $request, Order $order)
    {
        $request->validate([
            'craftsman_id' => ['required', 'exists:craftsmen,id']
        ]);

        $order->update([
            'craftsman_id' => $request->craftsman_id,
            'status' => 'allocated'
        ]);

        $craftsman = Craftsman::find($request->craftsman_id);
        $order->logActivity(
            action: 'allocated',
            note: "Order allocated to Craftsman: {$craftsman?->name}",
            details: [
                'craftsman_id'   => $craftsman?->id,
                'craftsman_name' => $craftsman?->name,
            ]
        );

        return back()->with('success', 'Order allocated to craftsman successfully.');
    }

    public function forceComplete(Order $order)
    {
        $order->update([
            'status' => 'completed'
        ]);

        $order->logActivity(
            action: 'force_completed',
            note: "Order marked as Force Completed by Admin.",
        );

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