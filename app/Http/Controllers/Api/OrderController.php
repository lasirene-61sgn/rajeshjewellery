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
     * If filter options are queried (e.g. ?filter=product_name), delegates to filters().
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->isFilterOptionsRequest($request)) {
            return $this->filters($request);
        }

        if ($user instanceof Admin) {
            return $this->adminIndex($request);
        }

        if ($user instanceof Craftsman) {
            return $this->craftsmanIndex($request, $user);
        }

        return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
    }

    /**
     * Dedicated Endpoint: Order types only.
     */
    public function orderTypes(Request $request): JsonResponse
    {
        $request->merge(['type' => 'order_type']);
        return $this->filters($request);
    }

    /**
     * Get filter options for Customer Orders.
     */
    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user instanceof Admin;
        $isCraftsman = $user instanceof Craftsman;

        if (! $isAdmin && ! $isCraftsman) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
        }

        $rawType = $request->input('type')
            ?? $request->input('filter')
            ?? $request->input('field')
            ?? $request->input('name')
            ?? $request->input('key')
            ?? $request->input('filter_type')
            ?? '';

        if ($rawType === '') {
            if ($request->has('order_type') || $request->has('order_types')) {
                $rawType = 'order_type';
            } elseif ($request->has('product_name') || $request->has('product_names')) {
                $rawType = 'product_name';
            } elseif ($request->has('craftsman_name') || $request->has('craftsman') || $request->has('craftsmen')) {
                $rawType = 'craftsman';
            }
        }

        $type = strtolower(trim((string) $rawType));
        $type = str_replace(['-', ' '], '_', $type);

        $normalizedType = match ($type) {
            'product', 'products', 'product_names', 'product_name' => 'product_name',
            'order_type', 'order_types', 'type', 'types' => 'order_type',
            'craftsman', 'craftsmen', 'craftsman_name', 'craftsman_names' => 'craftsman',
            'status', 'statuses', 'tab', 'tabs' => 'status',
            default => empty($type) ? 'all' : $type,
        };

        // 1. Single filter requested
        if ($normalizedType === 'product_name') {
            $query = Order::query();
            if (! $isAdmin) {
                $query->where('craftsman_id', $user->id);
            }
            $query->whereNotNull('product_name')->where('product_name', '!=', '');
            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where('product_name', 'like', "%{$q}%");
            }
            $products = $query->distinct()->orderBy('product_name')->pluck('product_name')->values();

            return response()->json([
                'success' => true,
                'role'    => $isAdmin ? 'admin' : 'craftsman',
                'filter'  => 'product_name',
                'data'    => $products,
                'items'   => $products,
            ]);
        }

        if ($normalizedType === 'order_type') {
            $query = Order::query();
            if (! $isAdmin) {
                $query->where('craftsman_id', $user->id);
            }
            $query->whereNotNull('order_type')->where('order_type', '!=', '');
            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where('order_type', 'like', "%{$q}%");
            }
            $types = $query->distinct()->orderBy('order_type')->pluck('order_type')->values();

            return response()->json([
                'success' => true,
                'role'    => $isAdmin ? 'admin' : 'craftsman',
                'filter'  => 'order_type',
                'data'    => $types,
                'items'   => $types,
            ]);
        }

        if ($normalizedType === 'craftsman') {
            // "craftsman name for admin only not craftsman panel"
            if (! $isAdmin) {
                return response()->json([
                    'success' => false,
                    'role'    => 'craftsman',
                    'message' => 'Craftsman filter is available for Admin only.',
                ], 403);
            }

            $craftsmen = Craftsman::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))
                ->orderBy('name')
                ->get(['id', 'name', 'mobile', 'email']);

            return response()->json([
                'success' => true,
                'role'    => 'admin',
                'filter'  => 'craftsman',
                'data'    => $craftsmen,
                'names'   => $craftsmen->pluck('name')->values(),
                'items'   => $craftsmen,
            ]);
        }

        // 2. All filters
        if ($isAdmin) {
            $products = Order::whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values();
            $types = Order::whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values();
            $craftsmen = Craftsman::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))->orderBy('name')->get(['id', 'name', 'mobile', 'email']);

            return response()->json([
                'success' => true,
                'role'    => 'admin',
                'data'    => [
                    'product_names' => $products,
                    'order_types'   => $types,
                    'craftsmen'     => $craftsmen, // Admin only!
                    'statuses'      => ['pending', 'allocated', 'in_process', 'returned', 'completed'],
                ],
            ]);
        }

        // Craftsman panel
        $products = Order::where('craftsman_id', $user->id)->whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values();
        $types = Order::where('craftsman_id', $user->id)->whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values();

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => [
                'product_names' => $products,
                'order_types'   => $types,
                'statuses'      => ['allocated', 'in_process', 'completed'],
            ],
        ]);
    }

    /**
     * Check if request asks for filter options.
     * Detects explicit filter parameters (?type=order_type) AND direct bare flags (/api/customer-orders?order_type).
     */
    protected function isFilterOptionsRequest(Request $request): bool
    {
        if ($request->has('get_filters') || $request->has('filter_options') || $request->has('get_filter') || $request->has('all_filters')) {
            return true;
        }

        $filterVal = strtolower(trim((string) ($request->input('filter') ?? $request->input('type') ?? $request->input('field') ?? '')));
        $validFilterTypes = ['product_name', 'product_names', 'product', 'products', 'order_type', 'order_types', 'craftsman', 'craftsmen', 'craftsman_name', 'status', 'statuses', 'all_filters', 'filters'];

        if ($request->has('filter') && in_array($filterVal, $validFilterTypes, true)) {
            return true;
        }

        if ($request->has('type') && in_array($filterVal, $validFilterTypes, true)) {
            return true;
        }

        if ($request->has('field') && in_array($filterVal, $validFilterTypes, true)) {
            return true;
        }

        // Direct query parameter flags (e.g. /api/customer-orders?order_type or ?product_name or ?craftsman_name)
        $filterKeys = ['order_type', 'order_types', 'product_name', 'product_names', 'craftsman_name', 'craftsman', 'craftsmen'];
        foreach ($filterKeys as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                if ($val === null || $val === '' || in_array(strtolower(trim((string) $val)), ['1', 'true', 'all', 'yes', 'list', 'filter', 'options'], true)) {
                    return true;
                }

                if ($request->has('q') || $request->has('search_filter')) {
                    return true;
                }

                if (! $request->has('tab') && ! $request->has('status') && ! $request->has('page') && ! $request->has('per_page') && ! $request->has('search') && ! $request->has('ref_no') && ! $request->has('reference_number')) {
                    return true;
                }
            }
        }

        return false;
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
     * Apply keyword and multi-ref search filters to a customer orders query.
     */
    protected function applySearchFilter(mixed $query, ?string $search): void
    {
        if (blank($search)) {
            return;
        }

        $rawSearch = trim((string) $search);

        // Check for multiple tokens separated by commas, semicolons, or newlines
        $multiTokens = preg_split('/[\r\n,;]+/', $rawSearch, -1, PREG_SPLIT_NO_EMPTY);
        $multiTokens = array_values(array_filter(array_map('trim', $multiTokens)));

        // Space-separated tokens resembling reference numbers
        if (count($multiTokens) === 1) {
            $spaceTokens = preg_split('/\s+/', $multiTokens[0], -1, PREG_SPLIT_NO_EMPTY);
            if (count($spaceTokens) > 1 && collect($spaceTokens)->every(fn ($t) => preg_match('/^(REF|ORD|\d|[A-Z]{2,}-\d)/i', $t))) {
                $multiTokens = $spaceTokens;
            }
        }

        if (count($multiTokens) > 1) {
            $query->where(function ($q) use ($multiTokens) {
                $q->whereIn('reference_number', $multiTokens);

                if (count($multiTokens) <= 50) {
                    foreach ($multiTokens as $token) {
                        $q->orWhere('reference_number', 'like', "%{$token}%")
                            ->orWhere('product_name', 'like', "%{$token}%")
                            ->orWhere('order_type', 'like', "%{$token}%");
                    }
                }
            });

            return;
        }

        $query->where(function ($q) use ($rawSearch) {
            $q->where('reference_number', 'like', "%{$rawSearch}%")
                ->orWhere('product_name', 'like', "%{$rawSearch}%")
                ->orWhere('notes', 'like', "%{$rawSearch}%")
                ->orWhere('order_type', 'like', "%{$rawSearch}%")
                ->orWhere('size', 'like', "%{$rawSearch}%")
                ->orWhereHas('craftsman', function ($sub) use ($rawSearch) {
                    $sub->where('name', 'like', "%{$rawSearch}%")
                        ->orWhere('mobile', 'like', "%{$rawSearch}%");
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
                $q->whereIn('reference_number', $refs);

                if (count($refs) <= 50) {
                    foreach ($refs as $r) {
                        $q->orWhere('reference_number', 'like', "%{$r}%");
                    }
                }
            });
        }
    }

    /**
     * Internal: Admin listing logic.
     */
    protected function adminIndex(Request $request): JsonResponse
    {
        $query = Order::with('craftsman');

        $status = $request->input('status') ?? $request->input('tab') ?? $request->input('tabs') ?? 'all';
        $status = strtolower(trim((string) $status));
        $status = str_replace('-', '_', $status);
        if ($status === 'inprocess') {
            $status = 'in_process';
        }

        // 1. Reference number filter (ref_no, reference_number, reference_no)
        $refInput = $request->input('ref_no') ?? $request->input('reference_number') ?? $request->input('reference_no');
        if (! blank($refInput)) {
            $this->applyRefNoFilter($query, $refInput);
        }

        // 2. Keyword & multi-token search
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        // 3. Dropdown Filters
        if ($request->filled('craftsman_id')) {
            $query->where('craftsman_id', $request->craftsman_id);
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', "%" . trim($request->product_name) . "%");
        }

        $orderTypeInput = $request->input('order_type');
        if (! empty($orderTypeInput)) {
            $types = is_array($orderTypeInput)
                ? $orderTypeInput
                : array_filter(array_map('trim', explode(',', (string) $orderTypeInput)));
            if (! empty($types)) {
                $query->whereIn('order_type', $types);
            }
        }

        // 4. Date Filters
        $fromDate = $request->input('from_date') ?? $request->input('date_from');
        if (! blank($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        $toDate = $request->input('to_date') ?? $request->input('date_to');
        if (! blank($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        // Clone base query before status tab filter for accurate tab counts
        $baseQuery = clone $query;

        // 5. Tab / Status Filtering
        if (! empty($status) && $status !== 'all') {
            if (in_array($status, ['in_process', 'in_progress'])) {
                $query->whereIn('status', ['in_process', 'in_progress']);
            } else {
                $query->where('status', $status);
            }
        }

        $counts = [
            'all'         => (clone $baseQuery)->count(),
            'pending'     => (clone $baseQuery)->where('status', 'pending')->count(),
            'allocated'   => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_progress' => (clone $baseQuery)->whereIn('status', ['in_process', 'in_progress'])->count(),
            'returned'    => (clone $baseQuery)->where('status', 'returned')->count(),
            'completed'   => (clone $baseQuery)->where('status', 'completed')->count(),
        ];

        $tabs = [
            [
                'key'       => 'all',
                'label'     => 'All Orders',
                'count'     => $counts['all'],
                'is_active' => $status === 'all' || empty($status),
            ],
            [
                'key'       => 'pending',
                'label'     => 'Pending',
                'count'     => $counts['pending'],
                'is_active' => $status === 'pending',
            ],
            [
                'key'       => 'allocated',
                'label'     => 'Allocated',
                'count'     => $counts['allocated'],
                'is_active' => $status === 'allocated',
            ],
            [
                'key'       => 'in_progress',
                'label'     => 'In Progress',
                'count'     => $counts['in_progress'],
                'is_active' => in_array($status, ['in_progress', 'in_process']),
            ],
            [
                'key'       => 'returned',
                'label'     => 'Returned',
                'count'     => $counts['returned'],
                'is_active' => $status === 'returned',
            ],
            [
                'key'       => 'completed',
                'label'     => 'Completed',
                'count'     => $counts['completed'],
                'is_active' => $status === 'completed',
            ],
        ];

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        $items = $paginator->getCollection()->map(fn (Order $order) => $this->transformOrder($order, $request->user()));
        $pagination = $this->formatPagination($paginator);

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => array_merge([
                'current_tab' => $status ?: 'all',
                'counts'      => $counts,
                'tabs'        => $tabs,
                'items'       => $items,
                'data'        => $items,
            ], $pagination),
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

        // 1. Reference number filter
        $refInput = $request->input('ref_no') ?? $request->input('reference_number') ?? $request->input('reference_no');
        if (! blank($refInput)) {
            $this->applyRefNoFilter($query, $refInput);
        }

        // 2. Keyword & multi-token search
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', 'like', "%" . trim($request->product_name) . "%");
        }

        $orderTypeInput = $request->input('order_type');
        if (! empty($orderTypeInput)) {
            $types = is_array($orderTypeInput)
                ? $orderTypeInput
                : array_filter(array_map('trim', explode(',', (string) $orderTypeInput)));
            if (! empty($types)) {
                $query->whereIn('order_type', $types);
            }
        }

        $fromDate = $request->input('from_date') ?? $request->input('date_from');
        if (! blank($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        $toDate = $request->input('to_date') ?? $request->input('date_to');
        if (! blank($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        if ($request->filled('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }
        if ($request->filled('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        $baseQuery = clone $query;

        if (! empty($currentTab) && $currentTab !== 'all') {
            if (in_array($currentTab, ['in_process', 'in_progress'])) {
                $query->whereIn('status', ['in_process', 'in_progress']);
            } else {
                $query->where('status', $currentTab);
            }
        }

        $counts = [
            'all'         => (clone $baseQuery)->count(),
            'allocated'   => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_progress' => (clone $baseQuery)->whereIn('status', ['in_process', 'in_progress'])->count(),
            'completed'   => (clone $baseQuery)->where('status', 'completed')->count(),
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

        $items = $paginator->getCollection()->map(fn (Order $order) => $this->transformOrder($order, $craftsman));
        $pagination = $this->formatPagination($paginator);

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => array_merge([
                'current_tab' => $currentTab ?: 'all',
                'counts'      => $counts,
                'tabs'        => $tabs,
                'items'       => $items,
                'data'        => $items,
            ], $pagination),
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

