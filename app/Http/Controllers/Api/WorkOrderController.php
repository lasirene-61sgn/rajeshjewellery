<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\DesignCode;
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
     * If filter options are queried (e.g. ?filter=design_code), delegates to filters().
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // If client requested filter options directly via query (e.g. ?type=design_code or ?filter=design_code)
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
     * Dedicated Endpoint: Design codes only.
     */
    public function designCodes(Request $request): JsonResponse
    {
        return $this->getDesignCodeFilterResponse($request, $request->user());
    }

    /**
     * Dedicated Endpoint: Design nicknames only.
     */
    public function nicknames(Request $request): JsonResponse
    {
        return $this->getDesignNicknameFilterResponse($request, $request->user());
    }

    /**
     * Dedicated Endpoint: Order types only.
     */
    public function orderTypes(Request $request): JsonResponse
    {
        return $this->getOrderTypeFilterResponse($request, $request->user());
    }

    /**
     * Dedicated Endpoint: Product names only.
     */
    public function productNames(Request $request): JsonResponse
    {
        return $this->getProductNameFilterResponse($request, $request->user());
    }

    /**
     * Dedicated Endpoint: Craftsmen directory filter (Admin only).
     */
    public function craftsmenFilter(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Craftsman filter is available for Admin only.',
            ], 403);
        }

        return $this->getCraftsmanFilterResponse($request);
    }

    /**
     * Dedicated Endpoint: Categories only.
     */
    public function categories(Request $request): JsonResponse
    {
        return $this->getCategoryFilterResponse($request, $request->user());
    }

    /**
     * Dedicated Endpoint: Subcategories only.
     */
    public function subcategories(Request $request): JsonResponse
    {
        return $this->getSubcategoryFilterResponse($request, $request->user());
    }

    /**
     * Get filter options (design codes, product names, order types, craftsman names for admin only, design nicknames).
     * When a specific type is requested (e.g. ?type=design_code or ?design_nickname), returns only that filter's list.
     */
    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user instanceof Admin;
        $isCraftsman = $user instanceof Craftsman;

        if (! $isAdmin && ! $isCraftsman) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
        }

        $normalizedType = $this->resolveFilterType($request);

        // 1. Single Filter Requested
        if ($normalizedType === 'design_code') {
            return $this->getDesignCodeFilterResponse($request, $user);
        }

        if ($normalizedType === 'product_name') {
            return $this->getProductNameFilterResponse($request, $user);
        }

        if ($normalizedType === 'order_type') {
            return $this->getOrderTypeFilterResponse($request, $user);
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

            return $this->getCraftsmanFilterResponse($request);
        }

        if ($normalizedType === 'design_nickname') {
            return $this->getDesignNicknameFilterResponse($request, $user);
        }

        if ($normalizedType === 'category') {
            return $this->getCategoryFilterResponse($request, $user);
        }

        if ($normalizedType === 'subcategory') {
            return $this->getSubcategoryFilterResponse($request, $user);
        }

        // 2. All Filter Options
        return $this->getAllFiltersResponse($request, $user);
    }

    /**
     * Check if the incoming request asks for filter options rather than order listings.
     * Detects explicit filter parameters (?type=design_code) AND direct bare flags (/api/work-orders?design_nickname).
     */
    protected function isFilterOptionsRequest(Request $request): bool
    {
        if ($request->has('get_filters') || $request->has('filter_options') || $request->has('get_filter') || $request->has('all_filters')) {
            return true;
        }

        // 1. Explicit filter/type parameter
        $explicit = strtolower(trim((string) ($request->input('filter') ?? $request->input('type') ?? $request->input('field') ?? $request->input('get') ?? '')));
        if ($explicit !== '' && $this->normalizeFilterType($explicit) !== null) {
            return true;
        }

        // 2. Direct query parameter flags (e.g. /api/work-orders?design_nickname or ?product_name or ?design_code or ?craftsman_name or ?order_type)
        $filterKeys = [
            'design_nickname', 'design_nicknames', 'design_nick_name', 'nickname', 'nicknames', 'nick_name',
            'product_name', 'product_names', 'product', 'products',
            'design_code', 'design_codes', 'design', 'designs', 'code', 'codes',
            'craftsman_name', 'craftsman_names', 'craftsman', 'craftsmen',
            'order_type', 'order_types', 'ordertype', 'ordertypes',
            'category', 'categories',
            'subcategory', 'subcategories',
        ];

        foreach ($filterKeys as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                // If passed as flag without value or flag string (?design_nickname or ?design_nickname= or ?design_nickname=all)
                if ($val === null || $val === '' || in_array(strtolower(trim((string) $val)), ['1', 'true', 'all', 'yes', 'list', 'filter', 'options'], true)) {
                    return true;
                }

                // If passed with search query e.g. /api/work-orders?design_nickname&q=pea
                if ($request->has('q') || $request->has('search_filter')) {
                    return true;
                }

                // If not an order table navigation query (no tab, status, page, per_page, search, ref_no)
                if (! $request->has('tab') && ! $request->has('status') && ! $request->has('page') && ! $request->has('per_page') && ! $request->has('search') && ! $request->has('ref_no') && ! $request->has('reference_no')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve normalized filter type from request parameters.
     */
    protected function resolveFilterType(Request $request): string
    {
        // 1. Explicit type/filter parameter
        $explicit = strtolower(trim((string) (
            $request->input('type')
            ?? $request->input('filter')
            ?? $request->input('field')
            ?? $request->input('name')
            ?? $request->input('key')
            ?? $request->input('get')
            ?? $request->input('filter_type')
            ?? ''
        )));

        if ($explicit !== '') {
            $normalized = $this->normalizeFilterType($explicit);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        // 2. Direct query parameter flags (e.g. /api/work-orders?design_nickname or ?product_name or ?design_code or ?craftsman_name or ?order_type)
        $filterKeyMap = [
            'design_nickname'  => 'design_nickname',
            'design_nicknames' => 'design_nickname',
            'design_nick_name' => 'design_nickname',
            'nickname'         => 'design_nickname',
            'nicknames'        => 'design_nickname',
            'nick_name'        => 'design_nickname',
            'product_name'     => 'product_name',
            'product_names'    => 'product_name',
            'product'          => 'product_name',
            'products'         => 'product_name',
            'design_code'      => 'design_code',
            'design_codes'     => 'design_code',
            'design'           => 'design_code',
            'designs'          => 'design_code',
            'code'             => 'design_code',
            'codes'            => 'design_code',
            'craftsman_name'   => 'craftsman',
            'craftsman_names'  => 'craftsman',
            'craftsman'        => 'craftsman',
            'craftsmen'        => 'craftsman',
            'worker'           => 'craftsman',
            'workers'          => 'craftsman',
            'order_type'       => 'order_type',
            'order_types'      => 'order_type',
            'ordertype'        => 'order_type',
            'ordertypes'       => 'order_type',
            'category'         => 'category',
            'categories'       => 'category',
            'subcategory'      => 'subcategory',
            'subcategories'    => 'subcategory',
        ];

        foreach ($filterKeyMap as $key => $targetType) {
            if ($request->has($key)) {
                $val = trim((string) $request->input($key));
                if ($val !== '' && ! in_array(strtolower($val), ['1', 'true', 'all', 'yes', 'list', 'filter', 'options'], true)) {
                    if (! $request->filled('q') && ! $request->filled('search')) {
                        $request->merge(['q' => $val]);
                    }
                }
                return $targetType;
            }
        }

        return 'all';
    }

    /**
     * Normalize raw filter type string.
     */
    protected function normalizeFilterType(string $type): ?string
    {
        $type = strtolower(trim($type));
        $type = str_replace(['-', ' '], '_', $type);

        return match ($type) {
            'design', 'designs', 'design_codes', 'design_code', 'code', 'codes' => 'design_code',
            'product', 'products', 'product_names', 'product_name' => 'product_name',
            'order_type', 'order_types', 'type', 'types', 'ordertype', 'ordertypes' => 'order_type',
            'craftsman', 'craftsmen', 'craftsman_name', 'craftsman_names', 'worker', 'workers' => 'craftsman',
            'nickname', 'nicknames', 'design_nickname', 'design_nicknames', 'design_nick_name', 'nick_name' => 'design_nickname',
            'category', 'categories' => 'category',
            'subcategory', 'subcategories' => 'subcategory',
            'all', 'all_filters', 'filters', 'filter_options' => 'all',
            default => null,
        };
    }

    /**
     * Filter Option: Design codes only.
     */
    protected function getDesignCodeFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;

        if ($isAdmin) {
            $query = DesignCode::orderBy('code');
            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where(fn ($sub) => $sub->where('code', 'like', "%{$q}%")->orWhere('nickname', 'like', "%{$q}%"));
            }
            $designs = $query->get(['id', 'code', 'nickname']);
            $codes = $designs->pluck('code')->values();
        } else {
            // Craftsman panel: designs assigned to self
            $query = $user->designCodes()->orderBy('code');
            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where(fn ($sub) => $sub->where('code', 'like', "%{$q}%")->orWhere('nickname', 'like', "%{$q}%"));
            }
            $designs = $query->get(['design_codes.id', 'design_codes.code', 'design_codes.nickname']);

            // Fallback to distinct design codes from own work orders if pivot table has none
            if ($designs->isEmpty()) {
                $woQuery = WorkOrder::where('craftsman_id', $user->id)
                    ->whereNotNull('design_code')
                    ->where('design_code', '!=', '');
                if ($request->filled('q') || $request->filled('search')) {
                    $q = trim((string) ($request->q ?? $request->search));
                    $woQuery->where('design_code', 'like', "%{$q}%");
                }
                $codes = $woQuery->distinct()->orderBy('design_code')->pluck('design_code')->values();
                $designs = $codes->map(fn ($c) => ['code' => $c, 'nickname' => null]);
            } else {
                $codes = $designs->pluck('code')->values();
            }
        }

        return response()->json([
            'success'      => true,
            'role'         => $isAdmin ? 'admin' : 'craftsman',
            'filter'       => 'design_code',
            'data'         => $codes,
            'design_codes' => $codes,
            'codes'        => $codes,
            'items'        => $designs,
        ]);
    }

    /**
     * Filter Option: Product names only.
     */
    protected function getProductNameFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;
        $query = WorkOrder::query();

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
            'success'       => true,
            'role'          => $isAdmin ? 'admin' : 'craftsman',
            'filter'        => 'product_name',
            'data'          => $products,
            'product_names' => $products,
            'items'         => $products,
        ]);
    }

    /**
     * Filter Option: Order types only.
     */
    protected function getOrderTypeFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;
        $query = WorkOrder::query();

        if (! $isAdmin) {
            $query->where('craftsman_id', $user->id);
        }

        $query->whereNotNull('order_type')->where('order_type', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where('order_type', 'like', "%{$q}%");
        }

        $types = $query->distinct()->orderBy('order_type')->pluck('order_type')->values();

        if ($types->isEmpty()) {
            $types = collect(['Regular', 'Custom', 'Urgent', 'Repair']);
        }

        return response()->json([
            'success'     => true,
            'role'        => $isAdmin ? 'admin' : 'craftsman',
            'filter'      => 'order_type',
            'data'        => $types,
            'order_types' => $types,
            'items'       => $types,
        ]);
    }

    /**
     * Filter Option: Craftsmen only (ADMIN ONLY).
     */
    protected function getCraftsmanFilterResponse(Request $request): JsonResponse
    {
        $query = Craftsman::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->orderBy('name');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where(fn ($sub) => $sub->where('name', 'like', "%{$q}%")->orWhere('mobile', 'like', "%{$q}%"));
        }

        $craftsmen = $query->get(['id', 'name', 'mobile', 'email']);
        $names = $craftsmen->pluck('name')->values();

        return response()->json([
            'success'         => true,
            'role'            => 'admin',
            'filter'          => 'craftsman_name',
            'data'            => $names,
            'craftsman_names' => $names,
            'names'           => $names,
            'craftsmen'       => $craftsmen,
            'items'           => $craftsmen,
        ]);
    }

    /**
     * Filter Option: Design nicknames only.
     */
    protected function getDesignNicknameFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;

        if ($isAdmin) {
            $query = DesignCode::whereNotNull('nickname')->where('nickname', '!=', '');
            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where('nickname', 'like', "%{$q}%");
            }
            $nicknames = $query->orderBy('nickname')->pluck('nickname')->unique()->values();
        } else {
            $query = DesignCode::whereHas('craftsmen', fn ($q) => $q->where('craftsmen.id', $user->id))
                ->whereNotNull('nickname')
                ->where('nickname', '!=', '');

            if ($request->filled('q') || $request->filled('search')) {
                $q = trim((string) ($request->q ?? $request->search));
                $query->where('nickname', 'like', "%{$q}%");
            }
            $nicknames = $query->pluck('nickname')->unique()->values();

            if ($nicknames->isEmpty()) {
                $woQuery = WorkOrder::where('craftsman_id', $user->id)
                    ->whereNotNull('design_nickname')
                    ->where('design_nickname', '!=', '');
                if ($request->filled('q') || $request->filled('search')) {
                    $q = trim((string) ($request->q ?? $request->search));
                    $woQuery->where('design_nickname', 'like', "%{$q}%");
                }
                $nicknames = $woQuery->distinct()->pluck('design_nickname')->values();
            }
        }

        return response()->json([
            'success'          => true,
            'role'             => $isAdmin ? 'admin' : 'craftsman',
            'filter'           => 'design_nickname',
            'data'             => $nicknames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
            'items'            => $nicknames,
        ]);
    }

    /**
     * Filter Option: Categories only.
     */
    protected function getCategoryFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;
        $query = WorkOrder::query();

        if (! $isAdmin) {
            $query->where('craftsman_id', $user->id);
        }

        $query->whereNotNull('category')->where('category', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where('category', 'like', "%{$q}%");
        }

        $categories = $query->distinct()->orderBy('category')->pluck('category')->values();

        return response()->json([
            'success'    => true,
            'role'       => $isAdmin ? 'admin' : 'craftsman',
            'filter'     => 'category',
            'data'       => $categories,
            'categories' => $categories,
            'items'      => $categories,
        ]);
    }

    /**
     * Filter Option: Subcategories only.
     */
    protected function getSubcategoryFilterResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;
        $query = WorkOrder::query();

        if (! $isAdmin) {
            $query->where('craftsman_id', $user->id);
        }

        $query->whereNotNull('subcategory')->where('subcategory', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where('subcategory', 'like', "%{$q}%");
        }

        $subcategories = $query->distinct()->orderBy('subcategory')->pluck('subcategory')->values();

        return response()->json([
            'success'       => true,
            'role'          => $isAdmin ? 'admin' : 'craftsman',
            'filter'        => 'subcategory',
            'data'          => $subcategories,
            'subcategories' => $subcategories,
            'items'         => $subcategories,
        ]);
    }

    /**
     * All Filter Options combined.
     */
    protected function getAllFiltersResponse(Request $request, $user): JsonResponse
    {
        $isAdmin = $user instanceof Admin;

        if ($isAdmin) {
            $designCodes = DesignCode::orderBy('code')->get(['id', 'code', 'nickname']);
            $productNames = WorkOrder::whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values();
            $orderTypes = WorkOrder::whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values();
            if ($orderTypes->isEmpty()) {
                $orderTypes = collect(['Regular', 'Custom', 'Urgent', 'Repair']);
            }
            $craftsmen = Craftsman::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))->orderBy('name')->get(['id', 'name', 'mobile', 'email']);
            $designNicknames = DesignCode::whereNotNull('nickname')->where('nickname', '!=', '')->orderBy('nickname')->pluck('nickname')->unique()->values();
            $categories = WorkOrder::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->values();
            $subcategories = WorkOrder::whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->orderBy('subcategory')->pluck('subcategory')->values();

            return response()->json([
                'success' => true,
                'role'    => 'admin',
                'data'    => [
                    'design_codes'     => $designCodes->pluck('code')->values(),
                    'designs'          => $designCodes,
                    'product_names'    => $productNames,
                    'order_types'      => $orderTypes,
                    'craftsmen'        => $craftsmen, // Admin only!
                    'design_nicknames' => $designNicknames,
                    'categories'       => $categories,
                    'subcategories'    => $subcategories,
                ],
            ]);
        }

        // Craftsman panel
        $craftsmanDesignCodes = $user->designCodes()->orderBy('code')->get(['design_codes.id', 'design_codes.code', 'design_codes.nickname']);
        if ($craftsmanDesignCodes->isEmpty()) {
            $codes = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('design_code')->where('design_code', '!=', '')->distinct()->orderBy('design_code')->pluck('design_code')->values();
            $designs = $codes->map(fn ($c) => ['code' => $c, 'nickname' => null]);
            $designCodes = $codes;
        } else {
            $designCodes = $craftsmanDesignCodes->pluck('code')->values();
            $designs = $craftsmanDesignCodes;
        }

        $productNames = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values();
        $orderTypes = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values();
        if ($orderTypes->isEmpty()) {
            $orderTypes = collect(['Regular', 'Custom', 'Urgent', 'Repair']);
        }

        $designNicknames = DesignCode::whereHas('craftsmen', fn ($q) => $q->where('craftsmen.id', $user->id))
            ->whereNotNull('nickname')
            ->where('nickname', '!=', '')
            ->pluck('nickname')
            ->unique()
            ->values();
        if ($designNicknames->isEmpty()) {
            $designNicknames = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('design_nickname')->where('design_nickname', '!=', '')->distinct()->pluck('design_nickname')->values();
        }

        $categories = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->values();
        $subcategories = WorkOrder::where('craftsman_id', $user->id)->whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->orderBy('subcategory')->pluck('subcategory')->values();

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => [
                'design_codes'     => $designCodes,
                'designs'          => $designs,
                'product_names'    => $productNames,
                'order_types'      => $orderTypes,
                'design_nicknames' => $designNicknames,
                'categories'       => $categories,
                'subcategories'    => $subcategories,
                // Craftsmen is deliberately omitted for Craftsman panel
            ],
        ]);
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
     * Print slip for a single work order (returns HTML or JSON print data).
     */
    public function print(Request $request, WorkOrder $workOrder): mixed
    {
        $user = $request->user();

        if ($user instanceof Craftsman && $workOrder->craftsman_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This work order is not assigned to you.',
            ], 403);
        }

        $workOrder->load('craftsman');
        $workOrders = collect([$workOrder]);
        $viewName = ($user instanceof Craftsman) ? 'craftsman.work_orders.print' : 'admin.work_orders.print';

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success'    => true,
                'work_order' => $this->transformWorkOrder($workOrder, $user),
                'print_html' => view($viewName, compact('workOrders', 'workOrder'))->render(),
            ]);
        }

        return view($viewName, compact('workOrders', 'workOrder'));
    }

    /**
     * Bulk print slips for multiple work orders.
     */
    public function bulkPrint(Request $request): mixed
    {
        $validated = $request->validate([
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $user = $request->user();
        $query = WorkOrder::with('craftsman')->whereIn('id', $validated['order_ids']);

        if ($user instanceof Craftsman) {
            $query->where('craftsman_id', $user->id);
        }

        $workOrders = $query->get();

        if ($workOrders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No matching work orders found.',
            ], 404);
        }

        $viewName = ($user instanceof Craftsman) ? 'craftsman.work_orders.print' : 'admin.work_orders.print';

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json([
                'success'     => true,
                'count'       => $workOrders->count(),
                'work_orders' => $workOrders->map(fn ($wo) => $this->transformWorkOrder($wo, $user)),
                'print_html'  => view($viewName, compact('workOrders'))->render(),
            ]);
        }

        return view($viewName, compact('workOrders'));
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
     * Apply keyword and multi-ref search filters to a work orders query.
     */
    protected function applySearchFilter(mixed $query, mixed $search): void
    {
        if (blank($search)) {
            return;
        }

        if (is_array($search)) {
            $rawSearch = implode(',', array_filter(array_map('strval', $search)));
        } else {
            $rawSearch = trim((string) $search);
        }

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
                $q->whereIn('reference_no', $multiTokens)
                    ->orWhereIn('work_order_no', $multiTokens);

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

        // Single search term
        $query->where(function ($q) use ($rawSearch) {
            $q->where('work_order_no', 'like', "%{$rawSearch}%")
                ->orWhere('reference_no', 'like', "%{$rawSearch}%")
                ->orWhere('product_name', 'like', "%{$rawSearch}%")
                ->orWhere('design_code', 'like', "%{$rawSearch}%")
                ->orWhere('design_nickname', 'like', "%{$rawSearch}%")
                ->orWhere('seal', 'like', "%{$rawSearch}%")
                ->orWhereHas('craftsman', function ($sub) use ($rawSearch) {
                    $sub->where('name', 'like', "%{$rawSearch}%")
                        ->orWhere('mobile', 'like', "%{$rawSearch}%");
                })
                ->orWhereExists(function ($sub) use ($rawSearch) {
                    $sub->select(DB::raw(1))
                        ->from('design_codes')
                        ->whereColumn('design_codes.code', 'work_orders.design_code')
                        ->where('design_codes.nickname', 'like', "%{$rawSearch}%");
                });
        });
    }

    /**
     * Extract normalized search tokens (for multi-ref tag boxes and badges).
     */
    protected function extractSearchTokens(Request $request): array
    {
        $raw = $request->input('search') ?? $request->input('ref_no') ?? $request->input('reference_no');
        if (blank($raw)) {
            return [];
        }

        if (is_array($raw)) {
            $tokens = [];
            foreach ($raw as $item) {
                $split = preg_split('/[\r\n,;]+/', (string) $item, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($split as $t) {
                    $t = trim($t);
                    if ($t !== '' && ! in_array($t, $tokens, true)) {
                        $tokens[] = $t;
                    }
                }
            }
            return $tokens;
        }

        $tokens = preg_split('/[\r\n,;]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = array_values(array_filter(array_map('trim', $tokens)));

        if (count($tokens) === 1 && str_contains($tokens[0], ' ')) {
            $spaceTokens = preg_split('/\s+/', $tokens[0], -1, PREG_SPLIT_NO_EMPTY);
            if (count($spaceTokens) > 1 && collect($spaceTokens)->every(fn ($t) => preg_match('/^(REF|WO|\d|[A-Z]{2,}-\d)/i', $t))) {
                return array_values(array_filter(array_map('trim', $spaceTokens)));
            }
        }

        return $tokens;
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

    /**
     * Internal: Admin listing logic.
     */
    protected function adminIndex(Request $request): JsonResponse
    {
        $today = Carbon::today('Asia/Kolkata')->toDateString();
        $currentTab = $this->resolveTab($request, 'all');

        $query = WorkOrder::with('craftsman');

        // 1. Reference number filter (supports ref_no, reference_no, array, comma/space separated)
        $refInput = $request->input('ref_no') ?? $request->input('reference_no');
        if (! blank($refInput)) {
            $this->applyRefNoFilter($query, $refInput);
        }

        // 2. Keyword & multi-token search
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        // 3. Dropdown Filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        if ($request->filled('craftsman_id')) {
            $craftsmanId = (int) $request->craftsman_id;
            $query->where(function ($q) use ($craftsmanId) {
                $q->where('craftsman_id', $craftsmanId)
                    ->orWhereExists(function ($sub) use ($craftsmanId) {
                        $sub->select(DB::raw(1))
                            ->from('design_codes')
                            ->join('craftsman_design_code', 'design_codes.id', '=', 'craftsman_design_code.design_code_id')
                            ->whereColumn('design_codes.code', 'work_orders.design_code')
                            ->where('craftsman_design_code.craftsman_id', $craftsmanId);
                    });
            });
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
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

        if ($request->filled('return_due_date_from')) {
            $query->whereDate('return_due_date', '>=', $request->return_due_date_from);
        }
        if ($request->filled('return_due_date_to')) {
            $query->whereDate('return_due_date', '<=', $request->return_due_date_to);
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

        $items = $paginator->getCollection()->map(fn (WorkOrder $order) => $this->transformWorkOrder($order, $request->user()));
        $pagination = $this->formatPagination($paginator);

        $searchTokens = $this->extractSearchTokens($request);

        $filterOptions = [
            'design_codes'     => DesignCode::orderBy('code')->pluck('code')->values(),
            'product_names'    => WorkOrder::whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values(),
            'order_types'      => WorkOrder::whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values(),
            'craftsmen'        => Craftsman::where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))->orderBy('name')->get(['id', 'name', 'mobile']),
            'design_nicknames' => DesignCode::whereNotNull('nickname')->where('nickname', '!=', '')->orderBy('nickname')->pluck('nickname')->unique()->values(),
            'categories'       => WorkOrder::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->values(),
            'subcategories'    => WorkOrder::whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->orderBy('subcategory')->pluck('subcategory')->values(),
        ];
        if ($filterOptions['order_types']->isEmpty()) {
            $filterOptions['order_types'] = collect(['Regular', 'Custom', 'Urgent', 'Repair']);
        }

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => array_merge([
                'current_tab'        => $currentTab,
                'counts'             => $counts,
                'tabs'               => $tabs,
                'search_tokens'      => $searchTokens,
                'search_token_count' => count($searchTokens),
                'filter_options'     => $filterOptions,
                'filters'            => $filterOptions,
                'items'              => $items,
                'data'               => $items,
            ], $pagination),
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

        // 1. Reference number filter (supports ref_no, reference_no, array, comma/space separated)
        $refInput = $request->input('ref_no') ?? $request->input('reference_no');
        if (! blank($refInput)) {
            $this->applyRefNoFilter($query, $refInput);
        }

        // 2. Keyword & multi-token search
        if ($request->filled('search')) {
            $this->applySearchFilter($query, $request->search);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
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

        if ($request->filled('nickname')) {
            $nickname = trim($request->nickname);
            $query->where(function ($q) use ($nickname) {
                $q->where('design_nickname', $nickname)
                    ->orWhereIn('design_code', function ($sub) use ($nickname) {
                        $sub->select('code')->from('design_codes')->where('nickname', $nickname);
                    });
            });
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

        if ($request->filled('return_due_date_from')) {
            $query->whereDate('return_due_date', '>=', $request->return_due_date_from);
        }
        if ($request->filled('return_due_date_to')) {
            $query->whereDate('return_due_date', '<=', $request->return_due_date_to);
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

        $items = $paginator->getCollection()->map(fn (WorkOrder $order) => $this->transformWorkOrder($order, $craftsman));
        $pagination = $this->formatPagination($paginator);

        $searchTokens = $this->extractSearchTokens($request);

        $craftsmanFilterOptions = [
            'design_codes'     => $craftsman->designCodes()->orderBy('code')->pluck('code')->values(),
            'product_names'    => WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->orderBy('product_name')->pluck('product_name')->values(),
            'order_types'      => WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->orderBy('order_type')->pluck('order_type')->values(),
            'design_nicknames' => DesignCode::whereHas('craftsmen', fn ($q) => $q->where('craftsmen.id', $craftsman->id))->whereNotNull('nickname')->where('nickname', '!=', '')->pluck('nickname')->unique()->values(),
            'categories'       => WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category')->values(),
            'subcategories'    => WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->orderBy('subcategory')->pluck('subcategory')->values(),
        ];
        if ($craftsmanFilterOptions['order_types']->isEmpty()) {
            $craftsmanFilterOptions['order_types'] = collect(['Regular', 'Custom', 'Urgent', 'Repair']);
        }
        if ($craftsmanFilterOptions['design_codes']->isEmpty()) {
            $craftsmanFilterOptions['design_codes'] = WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('design_code')->where('design_code', '!=', '')->distinct()->orderBy('design_code')->pluck('design_code')->values();
        }
        if ($craftsmanFilterOptions['design_nicknames']->isEmpty()) {
            $craftsmanFilterOptions['design_nicknames'] = WorkOrder::where('craftsman_id', $craftsman->id)->whereNotNull('design_nickname')->where('design_nickname', '!=', '')->distinct()->pluck('design_nickname')->values();
        }

        return response()->json([
            'success' => true,
            'role'    => 'craftsman',
            'data'    => array_merge([
                'current_tab'        => $currentTab,
                'counts'             => $counts,
                'tabs'               => $tabs,
                'search_tokens'      => $searchTokens,
                'search_token_count' => count($searchTokens),
                'filter_options'     => $craftsmanFilterOptions,
                'filters'            => $craftsmanFilterOptions,
                'items'              => $items,
                'data'               => $items,
            ], $pagination),
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
            'seal'                => $order->seal ?? (stripos((string) $order->reference_no, 'ESO') !== false ? '916' : null),
            'has_seal'            => (bool) ($order->has_seal || stripos((string) $order->reference_no, 'ESO') !== false || !empty($order->seal)),
            'job_type'            => $order->job_type,
            'order_type'          => $order->order_type,
            'status'              => $order->status,
            'is_partially_completed' => ($order->status === 'partially_completed' || ($order->completed_quantity ?? 0) > 0),
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
            'print_url'           => url('/api/work-orders/' . $order->id . '/print'),
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

            $refInput = $request->input('ref_no') ?? $request->input('reference_no');
            if (! blank($refInput)) {
                $this->applyRefNoFilter($baseQuery, $refInput);
            }
            if ($request->filled('search')) {
                $this->applySearchFilter($baseQuery, $request->search);
            }
            if ($request->filled('category')) {
                $baseQuery->where('category', $request->category);
            }
            if ($request->filled('subcategory')) {
                $baseQuery->where('subcategory', $request->subcategory);
            }
            if ($request->filled('craftsman_id')) {
                $craftsmanId = (int) $request->craftsman_id;
                $baseQuery->where(function ($q) use ($craftsmanId) {
                    $q->where('craftsman_id', $craftsmanId)
                        ->orWhereExists(function ($sub) use ($craftsmanId) {
                            $sub->select(DB::raw(1))->from('design_codes')
                                ->join('craftsman_design_code', 'design_codes.id', '=', 'craftsman_design_code.design_code_id')
                                ->whereColumn('design_codes.code', 'work_orders.design_code')
                                ->where('craftsman_design_code.craftsman_id', $craftsmanId);
                        });
                });
            }
            if ($request->filled('design_code')) {
                $baseQuery->where('design_code', $request->design_code);
            }
            if ($request->filled('product_name')) {
                $baseQuery->where('product_name', 'like', "%" . trim($request->product_name) . "%");
            }
            $orderTypeInput = $request->input('order_type');
            if (! empty($orderTypeInput)) {
                $types = is_array($orderTypeInput)
                    ? $orderTypeInput
                    : array_filter(array_map('trim', explode(',', (string) $orderTypeInput)));
                if (! empty($types)) {
                    $baseQuery->whereIn('order_type', $types);
                }
            }
            $fromDate = $request->input('from_date') ?? $request->input('date_from');
            if (! blank($fromDate)) {
                $baseQuery->whereDate('created_at', '>=', $fromDate);
            }
            $toDate = $request->input('to_date') ?? $request->input('date_to');
            if (! blank($toDate)) {
                $baseQuery->whereDate('created_at', '<=', $toDate);
            }
            if ($request->filled('due_date_from')) {
                $baseQuery->whereDate('due_date', '>=', $request->due_date_from);
            }
            if ($request->filled('due_date_to')) {
                $baseQuery->whereDate('due_date', '<=', $request->due_date_to);
            }
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

            $refInput = $request->input('ref_no') ?? $request->input('reference_no');
            if (! blank($refInput)) {
                $this->applyRefNoFilter($baseQuery, $refInput);
            }
            if ($request->filled('search')) {
                $this->applySearchFilter($baseQuery, $request->search);
            }
            if ($request->filled('category')) {
                $baseQuery->where('category', $request->category);
            }
            if ($request->filled('subcategory')) {
                $baseQuery->where('subcategory', $request->subcategory);
            }
            if ($request->filled('design_code')) {
                $baseQuery->where('design_code', $request->design_code);
            }
            if ($request->filled('product_name')) {
                $baseQuery->where('product_name', 'like', "%" . trim($request->product_name) . "%");
            }
            $orderTypeInput = $request->input('order_type');
            if (! empty($orderTypeInput)) {
                $types = is_array($orderTypeInput)
                    ? $orderTypeInput
                    : array_filter(array_map('trim', explode(',', (string) $orderTypeInput)));
                if (! empty($types)) {
                    $baseQuery->whereIn('order_type', $types);
                }
            }
            if ($request->filled('nickname')) {
                $nickname = trim($request->nickname);
                $baseQuery->where(function ($q) use ($nickname) {
                    $q->where('design_nickname', $nickname)
                        ->orWhereIn('design_code', function ($sub) use ($nickname) {
                            $sub->select('code')->from('design_codes')->where('nickname', $nickname);
                        });
                });
            }
            $fromDate = $request->input('from_date') ?? $request->input('date_from');
            if (! blank($fromDate)) {
                $baseQuery->whereDate('created_at', '>=', $fromDate);
            }
            $toDate = $request->input('to_date') ?? $request->input('date_to');
            if (! blank($toDate)) {
                $baseQuery->whereDate('created_at', '<=', $toDate);
            }
            if ($request->filled('due_date_from')) {
                $baseQuery->whereDate('due_date', '>=', $request->due_date_from);
            }
            if ($request->filled('due_date_to')) {
                $baseQuery->whereDate('due_date', '<=', $request->due_date_to);
            }
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

