<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        $craftsmanId = Auth::guard('craftsman')->id();
        $currentTab = $request->query('tab', 'in_process');
        $perPage = (int) $request->query('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $today = Carbon::today()->toDateString();
        $query = WorkOrder::where('craftsman_id', $craftsmanId)->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('design_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
        }

        if ($request->filled('nickname')) {
            $nickname = $request->nickname;
            $query->where(function ($q) use ($nickname) {
                $q->where('design_nickname', $nickname)
                  ->orWhereIn('design_code', function ($sub) use ($nickname) {
                      $sub->select('code')->from('design_codes')->where('nickname', $nickname);
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status Tabs Logic (Direct In-Process on allocation, plus For Approval & Overdue)
        match ($currentTab) {
            'allocated'    => $query->where('status', 'allocated'),
            'in_process'   => $query->whereIn('status', ['in_process', 'returned']),
            'for_approval' => $query->where('status', 'for_approval'),
            'overdue'      => $query->where('status', '!=', 'completed')
                                    ->where(function ($q) use ($today) {
                                        $q->whereDate('return_due_date', '<', $today)
                                          ->orWhere(function ($sub) use ($today) {
                                              $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                                          });
                                    }),
            default        => $query->whereIn('status', ['in_process', 'returned']),
        };

        $counts = [
            'allocated'    => WorkOrder::where('craftsman_id', $craftsmanId)->where('status', 'allocated')->count(),
            'in_process'   => WorkOrder::where('craftsman_id', $craftsmanId)->whereIn('status', ['in_process', 'returned'])->count(),
            'for_approval' => WorkOrder::where('craftsman_id', $craftsmanId)->where('status', 'for_approval')->count(),
            'overdue'      => WorkOrder::where('craftsman_id', $craftsmanId)
                                      ->where('status', '!=', 'completed')
                                      ->where(function ($q) use ($today) {
                                          $q->whereDate('return_due_date', '<', $today)
                                            ->orWhere(function ($sub) use ($today) {
                                                $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                                            });
                                      })->count(),
        ];

        $craftsman = Auth::guard('craftsman')->user();
        $designCodes = $craftsman->designCodes()->orderBy('code')->get();
        $categories = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('category')->distinct()->pluck('category')->filter()->values();
        $subcategories = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('subcategory')->distinct()->pluck('subcategory')->filter()->values();
        
        $nicknames = DesignCode::whereHas('craftsmen', fn($q) => $q->where('craftsmen.id', $craftsmanId))
            ->pluck('nickname')->unique()->values();

        $workOrders = $query->paginate($perPage)->withQueryString();

        session(['craftsman_work_orders_url' => request()->fullUrl()]);

        return view('craftsman.work_orders.index', compact('workOrders', 'counts', 'currentTab', 'perPage', 'designCodes', 'categories', 'subcategories', 'nicknames'));
    }

    public function show(WorkOrder $workOrder): View
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $craftsmanId = Auth::guard('craftsman')->id();
        $prevOrder = WorkOrder::where('craftsman_id', $craftsmanId)->where('id', '<', $workOrder->id)->orderBy('id', 'desc')->first();
        $nextOrder = WorkOrder::where('craftsman_id', $craftsmanId)->where('id', '>', $workOrder->id)->orderBy('id', 'asc')->first();
        $backUrl = session('craftsman_work_orders_url', route('craftsman.work-orders.index'));

        return view('craftsman.work_orders.show', compact('workOrder', 'backUrl', 'prevOrder', 'nextOrder'));
    }

    public function accept(WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $workOrder->update([
            'status'      => 'in_process',
            'accepted_at' => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} accepted successfully.");
    }

    public function bulkAccept(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $craftsmanId = Auth::guard('craftsman')->id();
        $updated = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->where('status', 'allocated')
            ->update([
                'status'      => 'in_process',
                'accepted_at' => Carbon::now(),
            ]);

        return back()->with('success', "Successfully accepted {$updated} work order(s).");
    }

    public function submitForApproval(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $workOrder->update([
            'status'       => 'for_approval',
            'submitted_at' => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} submitted for QC approval.");
    }

    public function bulkSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $craftsmanId = Auth::guard('craftsman')->id();
        $updated = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->whereIn('status', ['in_process', 'returned'])
            ->update([
                'status'       => 'for_approval',
                'submitted_at' => Carbon::now(),
            ]);

        return back()->with('success', "Successfully submitted {$updated} work order(s) for QC approval.");
    }

    public function print(WorkOrder $workOrder): View
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }
        $workOrder->load('craftsman');
        return view('craftsman.work_orders.print', compact('workOrder'));
    }

    public function bulkPrint(Request $request): View
    {
        $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $craftsmanId = Auth::guard('craftsman')->id();
        $workOrders = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->with('craftsman')
            ->get();

        return view('craftsman.work_orders.print', compact('workOrders'));
    }
}