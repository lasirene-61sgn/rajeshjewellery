<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    /**
     * Display work orders assigned to the logged-in craftsman.
     */
    public function index(Request $request): View
    {
        $craftsmanId = Auth::guard('craftsman')->id();
        $currentTab = $request->query('tab', 'allocated');

        $query = WorkOrder::where('craftsman_id', $craftsmanId)->latest();

        // Search Filters
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('design_nickname', 'like', "%{$search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('subcategory')) {
            $query->where('subcategory', 'like', "%" . trim($request->subcategory) . "%");
        }
        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
        }
        if ($request->filled('nickname')) {
            $query->where('design_nickname', 'like', "%" . trim($request->nickname) . "%");
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status Tabs Logic
        match ($currentTab) {
            'allocated'    => $query->where('status', 'allocated'),
            'in_process'   => $query->whereIn('status', ['in_process', 'returned']),
            'for_approval' => $query->where('status', 'for_approval'),
            'completed'    => $query->where('status', 'completed'),
            default        => $query->where('status', 'allocated'),
        };

        // Tab counts (unfiltered by search/date for tab persistent sizes, but maybe filtered?)
        // Usually tab counts are unfiltered by search so tabs don't look empty when searching.
        $counts = [
            'allocated'    => WorkOrder::where('craftsman_id', $craftsmanId)->where('status', 'allocated')->count(),
            'in_process'   => WorkOrder::where('craftsman_id', $craftsmanId)->whereIn('status', ['in_process', 'returned'])->count(),
            'for_approval' => WorkOrder::where('craftsman_id', $craftsmanId)->where('status', 'for_approval')->count(),
            'completed'    => WorkOrder::where('craftsman_id', $craftsmanId)->where('status', 'completed')->count(),
        ];

        // Fetch user assigned design codes for filter dropdown
        $craftsman = Auth::guard('craftsman')->user();
        $designCodes = $craftsman->designCodes()->orderBy('code')->get();
        // Also fetch unique categories
        $categories = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->filter()->values();
        $subcategories = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->pluck('subcategory')->filter()->values();
        $nicknames = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('design_nickname')->where('design_nickname', '!=', '')->distinct()->pluck('design_nickname')->filter()->values();

        $workOrders = $query->paginate(15)->withQueryString();

        session(['craftsman_work_orders_url' => request()->fullUrl()]);

        return view('craftsman.work_orders.index', compact('workOrders', 'counts', 'currentTab', 'designCodes', 'categories', 'subcategories', 'nicknames'));
    }

    /**
     * Display specific order details.
     */
    public function show(WorkOrder $workOrder): View
    {
        // Enforce ownership: Craftsman can only view their own assigned orders
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $backUrl = session('craftsman_work_orders_url', route('craftsman.work-orders.index'));

        return view('craftsman.work_orders.show', compact('workOrder', 'backUrl'));
    }

    /**
     * Accept newly allocated work order (Moves: allocated -> in_process).
     */
    public function accept(WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($workOrder->status !== 'allocated') {
            return back()->withErrors(['error' => 'This order cannot be accepted in its current state.']);
        }

        $workOrder->update([
            'status'      => 'in_process',
            'accepted_at' => Carbon::now(),
        ]);

        return redirect()->route('craftsman.work-orders.index', ['tab' => 'in_process'])
            ->with('success', "Order {$workOrder->work_order_no} accepted and moved to In-Process.");
    }

    /**
     * Submit finished order to Admin for approval (Moves: in_process/returned -> for_approval).
     */
    public function submitForApproval(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        if (! in_array($workOrder->status, ['in_process', 'returned'], true)) {
            return back()->withErrors(['error' => 'Only active in-process or rework orders can be submitted for approval.']);
        }

        $workOrder->update([
            'status'       => 'for_approval',
            'submitted_at' => Carbon::now(),
        ]);

        return redirect()->route('craftsman.work-orders.index', ['tab' => 'for_approval'])
            ->with('success', "Order {$workOrder->work_order_no} submitted for QC inspection.");
    }
}