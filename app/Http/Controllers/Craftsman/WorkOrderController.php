<?php

namespace App\Http\Controllers\Craftsman;

use App\Http\Controllers\Controller;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $query = WorkOrder::where('craftsman_id', $craftsmanId)->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        // 1. Search & Multi-Ref Query (supports multiple work order nos, ref nos, and keywords)
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        if ($request->filled('reference_no')) {
            $this->applyRefNoFilter($query, $request->reference_no);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', $request->product_name);
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

        $baseQuery = clone $query;

        // Status Tabs Logic (All, In-Process, Partially Completed, For Approval, Completed, Overdue)
        match ($currentTab) {
            'all'                 => null,
            'allocated'           => $query->where('status', 'allocated'),
            'in_process'          => $query->whereIn('status', ['in_process', 'returned']),
            'rejected'            => $query->where('status', 'rejected'),
            'partially_completed' => $query->where('status', 'partially_completed'),
            'returned'            => $query->where('status', 'returned'),
            'for_approval'        => $query->where('status', 'for_approval'),
            'completed'           => $query->where('status', 'completed'),
            'overdue'             => $query->where('status', '!=', 'completed')
                ->where(function ($q) use ($today) {
                    $q->whereDate('return_due_date', '<', $today)
                        ->orWhere(function ($sub) use ($today) {
                            $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                        });
                }),
            default               => null,
        };

        $counts = [
            'all'                 => (clone $baseQuery)->count(),
            'allocated'           => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_process'          => (clone $baseQuery)->whereIn('status', ['in_process', 'returned'])->count(),
            'rejected'            => (clone $baseQuery)->where('status', 'rejected')->count(),
            'partially_completed' => (clone $baseQuery)->where('status', 'partially_completed')->count(),
            'for_approval'        => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'completed'           => (clone $baseQuery)->where('status', 'completed')->count(),
            'overdue'             => (clone $baseQuery)->where('status', '!=', 'completed')
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
        $productNames = WorkOrder::where('craftsman_id', $craftsmanId)->whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->pluck('product_name')->sort()->values();

        $nicknames = DesignCode::whereHas('craftsmen', fn($q) => $q->where('craftsmen.id', $craftsmanId))
            ->pluck('nickname')->unique()->values();

        $workOrders = $query->paginate($perPage)->withQueryString();

        session(['craftsman_work_orders_url' => request()->fullUrl()]);

        return view('craftsman.work_orders.index', compact('workOrders', 'counts', 'currentTab', 'perPage', 'designCodes', 'categories', 'subcategories', 'nicknames', 'productNames'));
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

        $craftsmanName = Auth::guard('craftsman')->user()?->name ?? 'Craftsman';

        $workOrder->update([
            'status'      => 'in_process',
            'accepted_at' => Carbon::now(),
        ]);

        $workOrder->logActivity(
            action: 'in_process',
            note: "Work order accepted by Craftsman {$craftsmanName}. Fabrication started (In Process).",
            details: [
                'craftsman_id'   => $workOrder->craftsman_id,
                'craftsman_name' => $craftsmanName,
            ]
        );

        return back()->with('success', "Order {$workOrder->work_order_no} accepted successfully.");
    }

    public function bulkAccept(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $craftsmanId = $craftsman->id;
        $craftsmanName = $craftsman->name ?? 'Craftsman';

        $orders = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->where('status', 'allocated')
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status'      => 'in_process',
                'accepted_at' => Carbon::now(),
            ]);

            $order->logActivity(
                action: 'in_process',
                note: "Work order accepted by Craftsman {$craftsmanName}. Fabrication started (In Process).",
                details: [
                    'craftsman_id'   => $craftsmanId,
                    'craftsman_name' => $craftsmanName,
                ]
            );
        }

        return back()->with('success', "Successfully accepted " . count($orders) . " work order(s).");
    }

    public function reject(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $craftsmanName = Auth::guard('craftsman')->user()?->name ?? 'Craftsman';

        $workOrder->update([
            'status'        => 'rejected',
            'reject_reason' => $request->reject_reason,
        ]);

        $workOrder->logActivity(
            action: 'rejected',
            note: "Rejected by Craftsman {$craftsmanName}. Reason: {$request->reject_reason}",
            details: [
                'reject_reason'  => $request->reject_reason,
                'craftsman_name' => $craftsmanName,
            ]
        );

        return back()->with('success', "Order {$workOrder->work_order_no} has been rejected.");
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'     => ['required', 'array'],
            'order_ids.*'   => ['exists:work_orders,id'],
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $craftsmanId = $craftsman->id;
        $craftsmanName = $craftsman->name ?? 'Craftsman';

        $orders = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->where('status', 'allocated')
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status'        => 'rejected',
                'reject_reason' => $request->reject_reason,
            ]);

            $order->logActivity(
                action: 'rejected',
                note: "Rejected by Craftsman {$craftsmanName}. Reason: {$request->reject_reason}",
                details: [
                    'reject_reason'  => $request->reject_reason,
                    'craftsman_name' => $craftsmanName,
                ]
            );
        }

        return back()->with('success', "Successfully rejected " . count($orders) . " work order(s).");
    }

    public function submitForApproval(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $craftsmanName = Auth::guard('craftsman')->user()?->name ?? 'Craftsman';

        $workOrder->update([
            'status'       => 'for_approval',
            'submitted_at' => Carbon::now(),
        ]);

        $workOrder->logActivity(
            action: 'for_approval',
            note: "Submitted by Craftsman {$craftsmanName} for QC inspection and approval.",
            details: [
                'craftsman_name' => $craftsmanName,
            ]
        );

        return back()->with('success', "Order {$workOrder->work_order_no} submitted for QC approval.");
    }

    public function bulkSubmit(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $craftsman = Auth::guard('craftsman')->user();
        $craftsmanId = $craftsman->id;
        $craftsmanName = $craftsman->name ?? 'Craftsman';

        $orders = WorkOrder::where('craftsman_id', $craftsmanId)
            ->whereIn('id', $request->order_ids)
            ->whereIn('status', ['in_process', 'returned', 'partially_completed'])
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status'       => 'for_approval',
                'submitted_at' => Carbon::now(),
            ]);

            $order->logActivity(
                action: 'for_approval',
                note: "Submitted by Craftsman {$craftsmanName} for QC inspection and approval.",
                details: [
                    'craftsman_name' => $craftsmanName,
                ]
            );
        }

        return back()->with('success', "Successfully submitted " . count($orders) . " work order(s) for QC approval.");
    }

    public function print(WorkOrder $workOrder): View
    {
        if ($workOrder->craftsman_id !== Auth::guard('craftsman')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $workOrder->load('craftsman');
        $workOrders = collect([$workOrder]);

        return view('craftsman.work_orders.print', compact('workOrders', 'workOrder'));
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

    /**
     * Apply flexible search filter supporting multi-token references, work order numbers, and keyword matching.
     */
    protected function applySearchFilter(mixed $query, mixed $search): void
    {
        $rawSearch = trim((string) $search);
        if ($rawSearch === '') {
            return;
        }

        // Check for multiple tokens separated by commas, semicolons, or newlines
        $multiTokens = preg_split('/[\r\n,;]+/', $rawSearch, -1, PREG_SPLIT_NO_EMPTY);
        $multiTokens = array_values(array_filter(array_map('trim', $multiTokens)));

        // If only 1 token resulted, check if it contains space-separated tokens resembling reference or work order numbers
        if (count($multiTokens) === 1) {
            $spaceTokens = preg_split('/\s+/', $multiTokens[0], -1, PREG_SPLIT_NO_EMPTY);
            if (count($spaceTokens) > 1 && collect($spaceTokens)->every(fn ($t) => preg_match('/^(REF|WO|\d|[A-Z]{2,}-\d)/i', $t))) {
                $multiTokens = $spaceTokens;
            }
        }

        if (count($multiTokens) > 1) {
            $query->where(function ($q) use ($multiTokens) {
                // Exact matching on indexed columns
                $q->whereIn('reference_no', $multiTokens)
                    ->orWhereIn('work_order_no', $multiTokens);

                // Partial matching across reference numbers, work order numbers, and details
                if (count($multiTokens) <= 50) {
                    foreach ($multiTokens as $token) {
                        $q->orWhere('reference_no', 'like', "%{$token}%")
                            ->orWhere('work_order_no', 'like', "%{$token}%")
                            ->orWhere('design_code', 'like', "%{$token}%")
                            ->orWhere('product_name', 'like', "%{$token}%");
                    }
                }
            });

            return;
        }

        // Standard single search term
        $query->where(function ($q) use ($rawSearch) {
            $q->where('work_order_no', 'like', "%{$rawSearch}%")
                ->orWhere('reference_no', 'like', "%{$rawSearch}%")
                ->orWhere('product_name', 'like', "%{$rawSearch}%")
                ->orWhere('design_code', 'like', "%{$rawSearch}%")
                ->orWhere('design_nickname', 'like', "%{$rawSearch}%")
                ->orWhere('seal', 'like', "%{$rawSearch}%")
                ->orWhereExists(function ($sub) use ($rawSearch) {
                    $sub->select(DB::raw(1))
                        ->from('design_codes')
                        ->whereColumn('design_codes.code', 'work_orders.design_code')
                        ->where('design_codes.nickname', 'like', "%{$rawSearch}%");
                });
        });
    }

    /**
     * Apply explicit reference number filter (supports single, array, comma/newline/space separated).
     */
    protected function applyRefNoFilter(mixed $query, mixed $referenceNo): void
    {
        if (blank($referenceNo)) {
            return;
        }

        $refs = is_array($referenceNo)
            ? $referenceNo
            : preg_split('/[\r\n,;\s]+/', (string) $referenceNo, -1, PREG_SPLIT_NO_EMPTY);
        $refs = array_values(array_filter(array_map('trim', $refs)));

        if (! empty($refs)) {
            $query->where(function ($q) use ($refs) {
                $q->whereIn('reference_no', $refs)
                    ->orWhereIn('work_order_no', $refs);

                if (count($refs) <= 50) {
                    foreach ($refs as $r) {
                        $q->orWhere('reference_no', 'like', "%{$r}%")
                            ->orWhere('work_order_no', 'like', "%{$r}%");
                    }
                }
            });
        }
    }
}
