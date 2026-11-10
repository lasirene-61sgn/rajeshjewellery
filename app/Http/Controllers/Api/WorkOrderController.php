<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    /**
     * Unified Work Orders listing.
     * Auto-detects Admin (system-wide view-only with full filters) vs Craftsman (scoped to self).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            return $this->adminIndex($request);
        }

        if ($user instanceof Craftsman) {
            return $this->craftsmanIndex($request, $user);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
    }

    /**
     * Get tab definitions and live counts only (without items) for app navigation bars.
     */
    public function tabs(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Admin) {
            $data = $this->getAdminTabsAndCounts($request);
            return response()->json([
                'success' => true,
                'role'    => 'admin',
                'data'    => $data,
            ]);
        }

        if ($user instanceof Craftsman) {
            $data = $this->getCraftsmanTabsAndCounts($request, $user);
            return response()->json([
                'success' => true,
                'role'    => 'craftsman',
                'data'    => $data,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
    }

    /**
     * Unified Work Order details.
     */
    public function show(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Craftsman && (int) $workOrder->craftsman_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This work order is not assigned to you.',
            ], 403);
        }

        $workOrder->load([
            'craftsman',
            'logs' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
        ]);

        $transformed = $this->transformWorkOrder($workOrder, $user);
        $transformed['logs'] = $workOrder->logs->map(fn ($log) => [
            'id'             => $log->id,
            'action'         => $log->action,
            'action_label'   => $log->action_label,
            'status'         => $log->status,
            'badge_color'    => $log->badge_color,
            'attempt_number' => $log->attempt_number,
            'actor_type'     => $log->actor_type,
            'actor_name'     => $log->actor_name,
            'note'           => $log->note,
            'details'        => $log->details,
            'created_at'     => $log->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            'created_at_iso' => $log->created_at?->timezone('Asia/Kolkata')->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'role'    => $user instanceof Admin ? 'admin' : 'craftsman',
            'data'    => $transformed,
        ]);
    }

    /**
     * Craftsman Action: Accept allocated work order.
     */
    public function accept(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        if ((int) $workOrder->craftsman_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ($workOrder->status !== 'allocated') {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be accepted in status '{$workOrder->status}'.",
            ], 422);
        }

        $workOrder->update([
            'status'      => 'in_process',
            'accepted_at' => Carbon::now(),
        ]);

        $workOrder->logActivity(
            action: 'in_process',
            note: "Work order accepted by Craftsman {$user->name}. Fabrication started (In Process).",
            details: [
                'craftsman_id'   => $user->id,
                'craftsman_name' => $user->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Work order {$workOrder->work_order_no} accepted successfully.",
            'data'    => $this->transformWorkOrder($workOrder->fresh(), $user),
        ]);
    }

    /**
     * Craftsman Action: Reject allocated work order.
     */
    public function reject(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        if ((int) $workOrder->craftsman_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ($workOrder->status !== 'allocated') {
            return response()->json([
                'success' => false,
                'message' => "Order cannot be rejected in status '{$workOrder->status}'.",
            ], 422);
        }

        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $workOrder->update([
            'status'        => 'rejected',
            'reject_reason' => $validated['reject_reason'],
        ]);

        $workOrder->logActivity(
            action: 'rejected',
            note: "Rejected by Craftsman {$user->name}. Reason: {$validated['reject_reason']}",
            details: [
                'craftsman_id'   => $user->id,
                'craftsman_name' => $user->name,
                'reject_reason'  => $validated['reject_reason'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Work order {$workOrder->work_order_no} has been rejected.",
            'data'    => $this->transformWorkOrder($workOrder->fresh(), $user),
        ]);
    }

    /**
     * Craftsman Action: Submit for QC Approval.
     */
    public function submitForApproval(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        if ((int) $workOrder->craftsman_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if (! in_array($workOrder->status, ['in_process', 'returned', 'partially_completed'])) {
            return response()->json([
                'success' => false,
                'message' => "Cannot submit order in status '{$workOrder->status}'.",
            ], 422);
        }

        $workOrder->update([
            'status'       => 'for_approval',
            'submitted_at' => Carbon::now(),
        ]);

        $workOrder->logActivity(
            action: 'for_approval',
            note: "Submitted by Craftsman {$user->name} for QC inspection and approval.",
            details: [
                'craftsman_name' => $user->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Work order {$workOrder->work_order_no} submitted for QC approval.",
            'data'    => $this->transformWorkOrder($workOrder->fresh(), $user),
        ]);
    }

    /**
     * Craftsman Action: Bulk Accept.
     */
    public function bulkAccept(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        $validated = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:work_orders,id'],
        ]);

        $orders = WorkOrder::whereIn('id', $validated['order_ids'])
            ->where('craftsman_id', $user->id)
            ->where('status', 'allocated')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No allocated work orders found matching the provided IDs.',
            ], 422);
        }

        $now = Carbon::now();
        foreach ($orders as $order) {
            $order->update([
                'status'      => 'in_process',
                'accepted_at' => $now,
            ]);

            $order->logActivity(
                action: 'in_process',
                note: "Work order accepted via bulk action by Craftsman {$user->name}.",
                details: ['craftsman_name' => $user->name]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "{$orders->count()} work orders accepted successfully.",
        ]);
    }

    /**
     * Craftsman Action: Bulk Submit for approval.
     */
    public function bulkSubmit(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        $validated = $request->validate([
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:work_orders,id'],
        ]);

        $orders = WorkOrder::whereIn('id', $validated['order_ids'])
            ->where('craftsman_id', $user->id)
            ->whereIn('status', ['in_process', 'returned', 'partially_completed'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No eligible work orders found for QC submission.',
            ], 422);
        }

        $now = Carbon::now();
        foreach ($orders as $order) {
            $order->update([
                'status'       => 'for_approval',
                'submitted_at' => $now,
            ]);

            $order->logActivity(
                action: 'for_approval',
                note: "Submitted in bulk by Craftsman {$user->name} for QC inspection.",
                details: ['craftsman_name' => $user->name]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "{$orders->count()} work orders submitted for approval successfully.",
        ]);
    }

    /**
     * Bulk Reject allocated/pending orders.
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'order_ids'     => ['required', 'array', 'min:1'],
            'order_ids.*'   => ['integer', 'exists:work_orders,id'],
            'reject_reason' => [$user instanceof Craftsman ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $reason = $validated['reject_reason'] ?? ($user instanceof Admin ? 'Rejected by Admin' : 'Rejected');

        if ($user instanceof Craftsman) {
            $orders = WorkOrder::whereIn('id', $validated['order_ids'])
                ->where('craftsman_id', $user->id)
                ->where('status', 'allocated')
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No allocated work orders found matching the provided IDs.',
                ], 422);
            }

            foreach ($orders as $order) {
                $order->update([
                    'status'        => 'rejected',
                    'reject_reason' => $reason,
                ]);

                $order->logActivity(
                    action: 'rejected',
                    note: "Rejected via bulk action by Craftsman {$user->name}. Reason: {$reason}",
                    details: [
                        'craftsman_id'   => $user->id,
                        'craftsman_name' => $user->name,
                        'reject_reason'  => $reason,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => "{$orders->count()} work orders rejected successfully.",
            ]);
        }

        if ($user instanceof Admin) {
            $orders = WorkOrder::whereIn('id', $validated['order_ids'])
                ->whereIn('status', ['pending', 'allocated'])
                ->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible work orders found matching the provided IDs.',
                ], 422);
            }

            foreach ($orders as $order) {
                $order->update([
                    'status'        => 'rejected',
                    'craftsman_id'  => null,
                    'reject_reason' => $reason,
                ]);

                $order->logActivity(
                    action: 'rejected',
                    note: "Rejected by Admin. Reason: {$reason}",
                    details: ['reason' => $reason]
                );
            }

            return response()->json([
                'success' => true,
                'message' => "{$orders->count()} work orders rejected successfully.",
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
    }

    /**
     * Resolve and normalize tab parameter from request (supports tab, tabs, status).
     */
    protected function resolveTab(Request $request, string $default): string
    {
        $val = $request->input('tab') ?? $request->input('tabs') ?? $request->input('status') ?? $default;
        $val = strtolower(trim((string) $val));
        $val = str_replace('-', '_', $val);

        return match ($val) {
            'inprocess'          => 'in_process',
            'partiallycompleted' => 'partially_completed',
            'forapproval'        => 'for_approval',
            default              => $val,
        };
    }

    /**
     * Resolve per-page limit supporting per_page, limit, and perPage with bounds check.
     */
    protected function resolvePerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $val = $request->input('per_page') ?? $request->input('limit') ?? $request->input('perPage') ?? $default;
        return min(max((int) $val, 1), $max);
    }

    /**
     * Format pagination metadata with navigation URLs and item boundaries matching standard Laravel paginator.
     */
    protected function formatPagination($paginator): array
    {
        return [
            'current_page'   => $paginator->currentPage(),
            'first_page_url' => $paginator->url(1),
            'from'           => $paginator->firstItem(),
            'last_page'      => $paginator->lastPage(),
            'last_page_url'  => $paginator->url($paginator->lastPage()),
            'links'          => method_exists($paginator, 'linkCollection')
                ? $paginator->linkCollection()->toArray()
                : [],
            'next_page_url'  => $paginator->nextPageUrl(),
            'path'           => $paginator->path(),
            'per_page'       => $paginator->perPage(),
            'prev_page_url'  => $paginator->previousPageUrl(),
            'to'             => $paginator->lastItem(),
            'total'          => $paginator->total(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }

    /**
     * Internal: Admin listing logic.
     */
    protected function adminIndex(Request $request): JsonResponse
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $currentTab = $this->resolveTab($request, 'all');

        $query = WorkOrder::with('craftsman');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('design_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('design_nickname', 'like', "%{$search}%")
                    ->orWhereHas('craftsman', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('subcategory')) $query->where('subcategory', $request->subcategory);
        if ($request->filled('craftsman_id')) $query->where('craftsman_id', $request->craftsman_id);
        if ($request->filled('design_code')) $query->where('design_code', $request->design_code);
        if ($request->filled('product_name')) $query->where('product_name', $request->product_name);
        if ($request->filled('order_type')) $query->where('order_type', $request->order_type);
        if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->from_date);
        if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->to_date);

        if ($request->filled('nickname')) {
            $nickFilter = trim($request->nickname);
            $query->where(function ($q) use ($nickFilter) {
                $q->where('design_nickname', $nickFilter)
                    ->orWhereExists(function ($sub) use ($nickFilter) {
                        $sub->select(DB::raw(1))
                            ->from('design_codes')
                            ->whereColumn('design_codes.code', 'work_orders.design_code')
                            ->where('design_codes.nickname', $nickFilter);
                    });
            });
        }

        $baseQuery = clone $query;
        $tabData = $this->getAdminTabsAndCounts($request, $baseQuery);
        $counts = $tabData['counts'];
        $tabs = $tabData['tabs'];

        match ($currentTab) {
            'pending'             => $query->where('status', 'pending'),
            'allocated'           => $query->where('status', 'allocated'),
            'in_process'          => $query->where('status', 'in_process'),
            'for_approval'        => $query->where('status', 'for_approval'),
            'partially_completed' => $query->where('status', 'partially_completed'),
            'completed'           => $query->where('status', 'completed'),
            'rejected'            => $query->where('status', 'rejected'),
            'returned'            => $query->where('status', 'returned'),
            'overdue'             => $query->where('status', '!=', 'completed')
                ->where(function ($q) use ($today) {
                    $q->where(function ($sub) use ($today) {
                        $sub->whereNotNull('return_due_date')->whereDate('return_due_date', '<', $today);
                    })->orWhere(function ($sub) use ($today) {
                        $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                    });
                }),
            default               => null,
        };

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => [
                'current_tab' => $currentTab,
                'counts'      => $counts,
                'tabs'        => $tabs,
                'items'       => $paginator->getCollection()->map(fn (WorkOrder $order) => $this->transformWorkOrder($order, $request->user())),
                'pagination'  => $this->formatPagination($paginator),
            ],
        ]);
    }

    /**
     * Internal: Craftsman listing logic.
     */
    protected function craftsmanIndex(Request $request, Craftsman $craftsman): JsonResponse
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $currentTab = $this->resolveTab($request, 'allocated');

        $query = WorkOrder::where('craftsman_id', $craftsman->id);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('design_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('design_nickname', 'like', "%{$search}%");
            });
        }

        if ($request->filled('design_code')) $query->where('design_code', $request->design_code);
        if ($request->filled('product_name')) $query->where('product_name', $request->product_name);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);

        if ($request->filled('nickname')) {
            $nickname = trim($request->nickname);
            $query->where(function ($q) use ($nickname) {
                $q->where('design_nickname', $nickname)
                    ->orWhereIn('design_code', function ($sub) use ($nickname) {
                        $sub->select('code')->from('design_codes')->where('nickname', $nickname);
                    });
            });
        }

        $baseQuery = clone $query;

        $tabData = $this->getCraftsmanTabsAndCounts($request, $craftsman, $baseQuery);
        $counts = $tabData['counts'];
        $tabs = $tabData['tabs'];

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
                    $q->where(function ($sub) use ($today) {
                        $sub->whereNotNull('return_due_date')->whereDate('return_due_date', '<', $today);
                    })->orWhere(function ($sub) use ($today) {
                        $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                    });
                }),
            default               => null,
        };

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => [
                'current_tab' => $currentTab,
                'counts'      => $counts,
                'tabs'        => $tabs,
                'items'       => $paginator->getCollection()->map(fn (WorkOrder $order) => $this->transformWorkOrder($order, $craftsman)),
                'pagination'  => $this->formatPagination($paginator),
            ],
        ]);
    }

    /**
     * Transform a WorkOrder model.
     */
    protected function transformWorkOrder(WorkOrder $order, $user): array
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $isOverdue = ($order->status !== 'completed') && (
            ($order->return_due_date && Carbon::parse($order->return_due_date)->toDateString() < $today) ||
            (!$order->return_due_date && $order->due_date && Carbon::parse($order->due_date)->toDateString() < $today)
        );

        $isReturned = ($order->status === 'returned' || ($order->return_count ?? 0) > 0);

        return [
            'id'                  => $order->id,
            'craftsman_serial_no' => $order->craftsman_serial_no,
            's_no'                => $order->craftsman_serial_no ?? $order->id,
            'sno'                 => $order->craftsman_serial_no ?? $order->id,
            'work_order_no'       => $order->work_order_no,
            'reference_no'        => $order->reference_no,
            'product_name'        => $order->product_name,
            'design_code'         => $order->design_code,
            'design_nickname'     => $order->effective_nickname,
            'category'            => $order->category,
            'subcategory'         => $order->subcategory,
            'size'                => $order->size,
            'quantity'            => (int) $order->quantity,
            'completed_quantity'  => (int) ($order->completed_quantity ?? 0),
            'balance_quantity'    => (int) $order->balance_quantity,
            'unit_type'           => $order->unit_type ?? 'PCS',
            'hallmark_purity'     => $order->hallmark_purity,
            'target_weight'       => (float) $order->target_weight,
            'seal'                => $order->seal,
            'has_seal'            => (bool) $order->has_seal,
            'job_type'            => $order->job_type,
            'order_type'          => $order->order_type,
            'status'              => $order->status,
            'is_returned'         => $isReturned,
            'display_status'      => $isReturned ? 'Returned' : ucfirst(str_replace('_', ' ', $order->status)),
            'is_overdue'          => $isOverdue,
            'due_date'            => $order->due_date?->format('Y-m-d'),
            'due_date_formatted'  => $order->due_date?->format('d M Y'),
            'return_due_date'     => $order->return_due_date?->format('Y-m-d'),
            'return_due_formatted'=> $order->return_due_date?->format('d M Y'),
            'return_count'        => (int) ($order->return_count ?? 0),
            'return_reason'       => $order->return_reason,
            'reject_reason'       => $order->reject_reason,
            'design_image_url'    => $order->design_image ? url('storage/' . $order->design_image) : null,
            'return_image_url'    => $order->return_image ? url('storage/' . $order->return_image) : null,
            'craftsman'           => $order->craftsman ? [
                'id'     => $order->craftsman->id,
                'name'   => $order->craftsman->name,
                'mobile' => $order->craftsman->mobile,
            ] : null,
            'actions'             => [
                'can_accept' => $user instanceof Craftsman && $order->status === 'allocated',
                'can_reject' => $user instanceof Craftsman && $order->status === 'allocated',
                'can_submit' => $user instanceof Craftsman && in_array($order->status, ['in_process', 'returned', 'partially_completed']),
            ],
            'timestamps'          => [
                'created_at'   => $order->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'allocated_at' => $order->allocated_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'accepted_at'  => $order->accepted_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'submitted_at' => $order->submitted_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'completed_at' => $order->completed_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'approved_at'  => $order->approved_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        ];
    }

    /**
     * Build Admin tab definitions and live counts.
     */
    protected function getAdminTabsAndCounts(Request $request, $baseQuery = null): array
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $currentTab = $this->resolveTab($request, 'all');

        if ($baseQuery === null) {
            $baseQuery = WorkOrder::query();
        }

        $counts = [
            'all'                 => (clone $baseQuery)->count(),
            'pending'             => (clone $baseQuery)->where('status', 'pending')->count(),
            'allocated'           => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_process'          => (clone $baseQuery)->where('status', 'in_process')->count(),
            'for_approval'        => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'partially_completed' => (clone $baseQuery)->where('status', 'partially_completed')->count(),
            'completed'           => (clone $baseQuery)->where('status', 'completed')->count(),
            'rejected'            => (clone $baseQuery)->where('status', 'rejected')->count(),
            'returned'            => (clone $baseQuery)->where('status', 'returned')->count(),
            'overdue'             => (clone $baseQuery)->where('status', '!=', 'completed')
                ->where(function ($q) use ($today) {
                    $q->where(function ($sub) use ($today) {
                        $sub->whereNotNull('return_due_date')->whereDate('return_due_date', '<', $today);
                    })->orWhere(function ($sub) use ($today) {
                        $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                    });
                })->count(),
        ];

        $tabs = [
            [
                'key'       => 'all',
                'label'     => 'All Orders',
                'count'     => $counts['all'],
                'is_active' => $currentTab === 'all',
                'color'     => 'slate',
                'dot_color' => '#64748b',
            ],
            [
                'key'       => 'pending',
                'label'     => 'Pending',
                'count'     => $counts['pending'],
                'is_active' => $currentTab === 'pending',
                'color'     => 'amber',
                'dot_color' => '#f59e0b',
            ],
            [
                'key'       => 'allocated',
                'label'     => 'Allocated',
                'count'     => $counts['allocated'],
                'is_active' => $currentTab === 'allocated',
                'color'     => 'sky',
                'dot_color' => '#0284c7',
            ],
            [
                'key'       => 'in_process',
                'label'     => 'In Process',
                'count'     => $counts['in_process'],
                'is_active' => $currentTab === 'in_process',
                'color'     => 'orange',
                'dot_color' => '#f97316',
            ],
            [
                'key'       => 'for_approval',
                'label'     => 'For Approval',
                'count'     => $counts['for_approval'],
                'is_active' => $currentTab === 'for_approval',
                'color'     => 'indigo',
                'dot_color' => '#6366f1',
            ],
            [
                'key'       => 'partially_completed',
                'label'     => 'Partially Completed',
                'count'     => $counts['partially_completed'],
                'is_active' => $currentTab === 'partially_completed',
                'color'     => 'purple',
                'dot_color' => '#a855f7',
            ],
            [
                'key'       => 'completed',
                'label'     => 'Completed',
                'count'     => $counts['completed'],
                'is_active' => $currentTab === 'completed',
                'color'     => 'emerald',
                'dot_color' => '#10b981',
            ],
            [
                'key'       => 'rejected',
                'label'     => 'Rejected',
                'count'     => $counts['rejected'],
                'is_active' => $currentTab === 'rejected',
                'color'     => 'rose',
                'dot_color' => '#f43f5e',
            ],
            [
                'key'       => 'returned',
                'label'     => 'Returned',
                'count'     => $counts['returned'],
                'is_active' => $currentTab === 'returned',
                'color'     => 'red',
                'dot_color' => '#ef4444',
            ],
            [
                'key'       => 'overdue',
                'label'     => 'Overdue',
                'count'     => $counts['overdue'],
                'is_active' => $currentTab === 'overdue',
                'color'     => 'red',
                'dot_color' => '#dc2626',
            ],
        ];

        return [
            'counts' => $counts,
            'tabs'   => $tabs,
        ];
    }

    /**
     * Build Craftsman tab definitions and live counts.
     */
    protected function getCraftsmanTabsAndCounts(Request $request, Craftsman $craftsman, $baseQuery = null): array
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $currentTab = $this->resolveTab($request, 'allocated');

        if ($baseQuery === null) {
            $baseQuery = WorkOrder::where('craftsman_id', $craftsman->id);
        }

        $counts = [
            'all'                 => (clone $baseQuery)->count(),
            'allocated'           => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_process'          => (clone $baseQuery)->whereIn('status', ['in_process', 'returned'])->count(),
            'rejected'            => (clone $baseQuery)->where('status', 'rejected')->count(),
            'partially_completed' => (clone $baseQuery)->where('status', 'partially_completed')->count(),
            'for_approval'        => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'returned'            => (clone $baseQuery)->where('status', 'returned')->count(),
            'completed'           => (clone $baseQuery)->where('status', 'completed')->count(),
            'overdue'             => (clone $baseQuery)->where('status', '!=', 'completed')
                ->where(function ($q) use ($today) {
                    $q->where(function ($sub) use ($today) {
                        $sub->whereNotNull('return_due_date')->whereDate('return_due_date', '<', $today);
                    })->orWhere(function ($sub) use ($today) {
                        $sub->whereNull('return_due_date')->whereDate('due_date', '<', $today);
                    });
                })->count(),
        ];

        $tabs = [
            [
                'key'       => 'allocated',
                'label'     => 'Allocated',
                'count'     => $counts['allocated'],
                'is_active' => $currentTab === 'allocated',
                'color'     => 'sky',
                'dot_color' => '#0284c7',
            ],
            [
                'key'       => 'in_process',
                'label'     => 'Order Pending',
                'count'     => $counts['in_process'],
                'is_active' => $currentTab === 'in_process',
                'color'     => 'orange',
                'dot_color' => '#f97316',
            ],
            [
                'key'       => 'rejected',
                'label'     => 'Rejected',
                'count'     => $counts['rejected'],
                'is_active' => $currentTab === 'rejected',
                'color'     => 'rose',
                'dot_color' => '#f43f5e',
            ],
            [
                'key'       => 'partially_completed',
                'label'     => 'Partially Completed',
                'count'     => $counts['partially_completed'],
                'is_active' => $currentTab === 'partially_completed',
                'color'     => 'purple',
                'dot_color' => '#a855f7',
            ],
            [
                'key'       => 'for_approval',
                'label'     => 'For Approval',
                'count'     => $counts['for_approval'],
                'is_active' => $currentTab === 'for_approval',
                'color'     => 'indigo',
                'dot_color' => '#6366f1',
            ],
            [
                'key'       => 'overdue',
                'label'     => 'Overdue',
                'count'     => $counts['overdue'],
                'is_active' => $currentTab === 'overdue',
                'color'     => 'rose',
                'dot_color' => '#e11d48',
            ],
            [
                'key'       => 'returned',
                'label'     => 'Returned',
                'count'     => $counts['returned'],
                'is_active' => $currentTab === 'returned',
                'color'     => 'red',
                'dot_color' => '#ef4444',
            ],
            [
                'key'       => 'completed',
                'label'     => 'Completed',
                'count'     => $counts['completed'],
                'is_active' => $currentTab === 'completed',
                'color'     => 'emerald',
                'dot_color' => '#10b981',
            ],
            [
                'key'       => 'all',
                'label'     => 'All Orders',
                'count'     => $counts['all'],
                'is_active' => $currentTab === 'all',
                'color'     => 'slate',
                'dot_color' => '#64748b',
            ],
        ];

        return [
            'counts' => $counts,
            'tabs'   => $tabs,
        ];
    }
}

