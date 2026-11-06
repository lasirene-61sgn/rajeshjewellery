<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Unified Customer Orders listing.
     * Auto-detects Admin (system-wide view-only) vs Craftsman (scoped to self).
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
     * Unified Customer Order details.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof Craftsman && (int) $order->craftsman_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This customer order is not assigned to you.',
            ], 403);
        }

        $order->load([
            'craftsman',
            'logs' => fn ($q) => $q->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
        ]);

        $transformed = $this->transformOrder($order, $user);
        $transformed['logs'] = $order->logs->map(fn ($log) => [
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
     * Craftsman Action: Update order status (in_progress, completed).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Craftsman) {
            return response()->json(['success' => false, 'message' => 'Action available to Craftsman only.'], 403);
        }

        if ((int) $order->craftsman_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:in_progress,completed'],
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        $statusLabel = ucwords(str_replace('_', ' ', $validated['status']));
        $order->logActivity(
            action: $validated['status'],
            note: "Order status updated to {$statusLabel} by Craftsman {$user->name}.",
            details: [
                'status'         => $validated['status'],
                'craftsman_name' => $user->name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Order status updated to {$statusLabel} successfully.",
            'data'    => $this->transformOrder($order->fresh(), $user),
        ]);
    }

    /**
     * Internal: Admin listing logic.
     */
    protected function adminIndex(Request $request): JsonResponse
    {
        $query = Order::with('craftsman');

        $status = $request->input('status') ?? $request->input('tab') ?? $request->input('tabs');
        if (! empty($status)) {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $counts = [
            'all'         => Order::count(),
            'pending'     => Order::where('status', 'pending')->count(),
            'allocated'   => Order::where('status', 'allocated')->count(),
            'in_progress' => Order::whereIn('status', ['in_process', 'in_progress'])->count(),
            'returned'    => Order::where('status', 'returned')->count(),
            'completed'   => Order::where('status', 'completed')->count(),
        ];

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => [
                'counts'     => $counts,
                'items'      => $paginator->getCollection()->map(fn (Order $order) => $this->transformOrder($order, $request->user())),
                'pagination' => $this->formatPagination($paginator),
            ],
        ]);
    }

    /**
     * Internal: Craftsman listing logic.
     */
    protected function craftsmanIndex(Request $request, Craftsman $craftsman): JsonResponse
    {
        $query = Order::where('craftsman_id', $craftsman->id);

        $currentTab = $request->input('tab') ?? $request->input('tabs') ?? $request->input('status') ?? 'all';
        $currentTab = strtolower(trim((string) $currentTab));
        $currentTab = str_replace('-', '_', $currentTab);

        if ($currentTab === 'inprocess') {
            $currentTab = 'in_progress';
        }

        if (! empty($currentTab) && $currentTab !== 'all') {
            if (in_array($currentTab, ['in_process', 'in_progress'])) {
                $query->whereIn('status', ['in_process', 'in_progress']);
            } else {
                $query->where('status', $currentTab);
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $counts = [
            'all'         => Order::where('craftsman_id', $craftsman->id)->count(),
            'allocated'   => Order::where('craftsman_id', $craftsman->id)->where('status', 'allocated')->count(),
            'in_progress' => Order::where('craftsman_id', $craftsman->id)->whereIn('status', ['in_process', 'in_progress'])->count(),
            'completed'   => Order::where('craftsman_id', $craftsman->id)->where('status', 'completed')->count(),
        ];

        $tabs = [
            [
                'key'       => 'all',
                'label'     => 'All Assigned',
                'count'     => $counts['all'],
                'is_active' => $currentTab === 'all' || empty($currentTab),
            ],
            [
                'key'       => 'allocated',
                'label'     => 'Allocated',
                'count'     => $counts['allocated'],
                'is_active' => $currentTab === 'allocated',
            ],
            [
                'key'       => 'in_progress',
                'label'     => 'In Progress',
                'count'     => $counts['in_progress'],
                'is_active' => in_array($currentTab, ['in_progress', 'in_process']),
            ],
            [
                'key'       => 'completed',
                'label'     => 'Completed',
                'count'     => $counts['completed'],
                'is_active' => $currentTab === 'completed',
            ],
        ];

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => [
                'current_tab' => $currentTab ?: 'all',
                'counts'      => $counts,
                'tabs'        => $tabs,
                'items'       => $paginator->getCollection()->map(fn (Order $order) => $this->transformOrder($order, $craftsman)),
                'pagination'  => $this->formatPagination($paginator),
            ],
        ]);
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
     * Format pagination metadata with navigation URLs and item boundaries.
     */
    protected function formatPagination($paginator): array
    {
        return [
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'per_page'       => $paginator->perPage(),
            'total'          => $paginator->total(),
            'from'           => $paginator->firstItem() ?? 0,
            'to'             => $paginator->lastItem() ?? 0,
            'has_more_pages' => $paginator->hasMorePages(),
            'next_page_url'  => $paginator->nextPageUrl(),
            'prev_page_url'  => $paginator->previousPageUrl(),
        ];
    }

    /**
     * Transform an Order model.
     */
    protected function transformOrder(Order $order, $user): array
    {
        return [
            'id'                 => $order->id,
            'reference_number'   => $order->reference_number,
            'order_no'           => $order->reference_number ?? $order->order_no ?? ('ORD-' . $order->id),
            'customer_name'      => $order->customer_name ?? null,
            'customer_phone'     => $order->customer_phone ?? null,
            'product_name'       => $order->product_name,
            'order_type'         => $order->order_type,
            'category'           => $order->category ?? null,
            'purity'             => $order->purity ?? null,
            'weight'             => $order->weight,
            'size'               => $order->size,
            'length'             => $order->length,
            'quantity'           => $order->quantity,
            'status'             => $order->status,
            'created_date'       => $order->created_date?->format('Y-m-d'),
            'due_date'           => $order->due_date?->format('Y-m-d'),
            'due_date_formatted' => $order->due_date?->format('d M Y'),
            'image_url'          => $order->image ? url('storage/' . $order->image) : null,
            'notes'              => $order->notes ?? $order->description ?? null,
            'craftsman'          => $order->craftsman ? [
                'id'     => $order->craftsman->id,
                'name'   => $order->craftsman->name,
                'mobile' => $order->craftsman->mobile,
            ] : null,
            'actions'            => [
                'can_mark_in_progress' => $user instanceof Craftsman && in_array($order->status, ['allocated', 'pending']),
                'can_mark_completed'   => $user instanceof Craftsman && in_array($order->status, ['in_progress', 'in_process']),
            ],
            'timestamps'         => [
                'created_at' => $order->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
                'updated_at' => $order->updated_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        ];
    }
}

