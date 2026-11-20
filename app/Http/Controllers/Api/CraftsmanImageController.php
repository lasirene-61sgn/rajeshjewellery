<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\DesignCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CraftsmanImageController extends Controller
{
    /**
     * View-only listing of craftsmen folders with assigned design code and image counts (Admin Only).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images directory is accessible to Admin only.',
            ], 403);
        }

        if ($this->isAllFiltersRequest($request)) {
            return $this->allFilters($request);
        }

        if ($this->isProductNamesFilterRequest($request)) {
            return $this->allProductNames($request);
        }

        if ($this->isNicknamesFilterRequest($request)) {
            return $this->allNicknames($request);
        }

        $query = Craftsman::query()
            ->withCount('designCodes')
            ->withCount([
                'designCodes as images_count' => function ($q) {
                    $q->whereNotNull('image')->where('image', '!=', '');
                }
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('work_orders')
                            ->whereColumn('work_orders.craftsman_id', 'craftsmen.id')
                            ->where('work_orders.product_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' || $request->status === '1');
        }

        $page = $this->resolvePage($request, 1);
        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        $items = $paginator->getCollection()->map(function (Craftsman $c) {
            return [
                'id'                 => $c->id,
                'name'               => $c->name,
                'email'              => $c->email,
                'mobile'             => $c->mobile,
                'is_active'          => (bool) $c->is_active,
                'design_codes_count' => (int) $c->design_codes_count,
                'images_count'       => (int) $c->images_count,
                'created_at'         => $c->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ];
        });

        $stats = [
            'total_craftsmen'    => Craftsman::count(),
            'total_design_codes' => DesignCode::count(),
            'total_images'       => DesignCode::whereNotNull('image')->where('image', '!=', '')->count(),
        ];

        $pagination = $this->formatPagination($paginator);

        return response()->json([
            'success'        => true,
            'role'           => 'admin',
            'pagination'     => $pagination,
            'meta'           => $pagination,
            'current_page'   => $pagination['current_page'],
            'last_page'      => $pagination['last_page'],
            'per_page'       => $pagination['per_page'],
            'total'          => $pagination['total'],
            'from'           => $pagination['from'],
            'to'             => $pagination['to'],
            'has_more_pages' => $pagination['has_more_pages'],
            'data'           => array_merge([
                'stats'      => $stats,
                'items'      => $items,
                'data'       => $items,
                'pagination' => $pagination,
                'meta'       => $pagination,
            ], $pagination),
        ]);
    }

    /**
     * View design codes and images assigned to a specific craftsman folder (Admin Only).
     */
    public function show(Request $request, Craftsman $craftsman): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images details are accessible to Admin only.',
            ], 403);
        }

        // Return both filters (product names and design nicknames) if filters=all, filters=both, filters=1, filters=true, filters=filters, filters
        if ($this->isAllFiltersRequest($request)) {
            return $this->filters($request, $craftsman);
        }

        // Return only product names list if filters=product_names requested
        if ($this->isProductNamesFilterRequest($request)) {
            return $this->productNames($request, $craftsman);
        }

        // Return only nicknames list if filters=nicknames or filters=design_nicknames requested
        if ($this->isNicknamesFilterRequest($request)) {
            return $this->nicknames($request, $craftsman);
        }

        $stats = [
            'total'          => $craftsman->designCodes()->count(),
            'with_images'    => $craftsman->designCodes()->whereNotNull('design_codes.image')->where('design_codes.image', '!=', '')->count(),
            'without_images' => $craftsman->designCodes()->where(function ($q) {
                $q->whereNull('design_codes.image')->orWhere('design_codes.image', '');
            })->count(),
        ];

        $query = $craftsman->designCodes()
            ->select('design_codes.*')
            ->addSelect([
                'product_name' => DB::table('work_orders')
                    ->select('product_name')
                    ->whereColumn('work_orders.design_code', 'design_codes.code')
                    ->whereNotNull('product_name')
                    ->where('product_name', '!=', '')
                    ->orderByRaw('CASE WHEN craftsman_id = ' . (int) $craftsman->id . ' THEN 0 ELSE 1 END')
                    ->orderBy('id', 'desc')
                    ->limit(1)
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('design_codes.code', 'like', "%{$search}%")
                    ->orWhere('design_codes.nickname', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('work_orders')
                            ->whereColumn('work_orders.design_code', 'design_codes.code')
                            ->where('work_orders.product_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('filter')) {
            if ($request->filter === 'with_image') {
                $query->whereNotNull('design_codes.image')->where('design_codes.image', '!=', '');
            } elseif ($request->filter === 'without_image') {
                $query->where(function ($q) {
                    $q->whereNull('design_codes.image')->orWhere('design_codes.image', '');
                });
            }
        }

        if ($request->filled('product_name') || $request->filled('product')) {
            $productFilter = trim((string) ($request->input('product_name') ?? $request->input('product')));
            $query->whereExists(function ($sub) use ($productFilter) {
                $sub->select(DB::raw(1))
                    ->from('work_orders')
                    ->whereColumn('work_orders.design_code', 'design_codes.code')
                    ->where(function ($w) use ($productFilter) {
                        $w->where('work_orders.product_name', $productFilter)
                          ->orWhere('work_orders.product_name', 'like', "%{$productFilter}%");
                    });
            });
        }

        if ($request->filled('nickname') || $request->filled('design_nickname')) {
            $nickFilter = trim((string) ($request->input('nickname') ?? $request->input('design_nickname')));
            $query->where(function ($q) use ($nickFilter) {
                $q->where('design_codes.nickname', $nickFilter)
                  ->orWhere('design_codes.nickname', 'like', "%{$nickFilter}%")
                  ->orWhereExists(function ($sub) use ($nickFilter) {
                      $sub->select(DB::raw(1))
                          ->from('work_orders')
                          ->whereColumn('work_orders.design_code', 'design_codes.code')
                          ->where('work_orders.design_nickname', $nickFilter);
                  });
            });
        }

        $page = $this->resolvePage($request, 1);
        $perPage = $this->resolvePerPage($request, 16);
        $paginator = $query->orderBy('design_codes.code')->paginate($perPage, ['*'], 'page', $page)->withQueryString();

        $items = $paginator->getCollection()->map(function (DesignCode $d) {
            return [
                'id'              => $d->id,
                'code'            => $d->code,
                'nickname'        => $d->nickname,
                'design_nickname' => $d->nickname,
                'product_name'    => $d->product_name ?? null,
                'has_image'       => ! empty($d->image),
                'image'           => $d->image,
                'image_url'       => $d->image ? url('storage/' . $d->image) : null,
                'created_at'      => $d->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
            ];
        });

        // Available product names and nicknames for filter options
        $productNames = $this->getCraftsmanProductNames($craftsman);
        $nicknames = $this->getCraftsmanNicknames($craftsman);

        $filtersPayload = [
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
        ];

        $pagination = $this->formatPagination($paginator);

        return response()->json([
            'success'          => true,
            'role'             => 'admin',
            'craftsman'        => [
                'id'        => $craftsman->id,
                'name'      => $craftsman->name,
                'email'     => $craftsman->email,
                'mobile'    => $craftsman->mobile,
                'is_active' => (bool) $craftsman->is_active,
            ],
            'stats'            => $stats,
            'filters'          => $filtersPayload,
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
            'items'            => $items,
            'pagination'       => $pagination,
            'meta'             => $pagination,
            'current_page'     => $pagination['current_page'],
            'last_page'        => $pagination['last_page'],
            'per_page'         => $pagination['per_page'],
            'total'            => $pagination['total'],
            'from'             => $pagination['from'],
            'to'               => $pagination['to'],
            'has_more_pages'   => $pagination['has_more_pages'],
            'data'             => array_merge([
                'craftsman'        => [
                    'id'        => $craftsman->id,
                    'name'      => $craftsman->name,
                    'email'     => $craftsman->email,
                    'mobile'    => $craftsman->mobile,
                    'is_active' => (bool) $craftsman->is_active,
                ],
                'stats'            => $stats,
                'product_names'    => $productNames,
                'nicknames'        => $nicknames,
                'design_nicknames' => $nicknames,
                'filters'          => $filtersPayload,
                'items'            => $items,
                'data'             => $items,
                'pagination'       => $pagination,
                'meta'             => $pagination,
            ], $pagination),
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: Both product names and design nicknames filter options for this craftsman (Admin Only).
     */
    public function filters(Request $request, Craftsman $craftsman): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images details are accessible to Admin only.',
            ], 403);
        }

        $productNames = $this->getCraftsmanProductNames($craftsman);
        $nicknames = $this->getCraftsmanNicknames($craftsman);

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $productNames = $productNames->filter(fn ($item) => stripos((string) $item, $q) !== false)->values();
            $nicknames = $nicknames->filter(fn ($item) => stripos((string) $item, $q) !== false)->values();
        }

        $filtersPayload = [
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
        ];

        return response()->json([
            'success'          => true,
            'role'             => 'admin',
            'craftsman'        => [
                'id'           => $craftsman->id,
                'name'         => $craftsman->name,
                'email'        => $craftsman->email,
                'mobile'       => $craftsman->mobile,
            ],
            'filter'           => 'all',
            'filters'          => $filtersPayload,
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
            'data'             => $filtersPayload,
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: All product names and design nicknames across craftsmen (Admin Only).
     */
    public function allFilters(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images directory is accessible to Admin only.',
            ], 403);
        }

        $prodQuery = DB::table('work_orders')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '');

        $nickQuery = DB::table('design_codes')
            ->whereNotNull('nickname')
            ->where('nickname', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $prodQuery->where('product_name', 'like', "%{$q}%");
            $nickQuery->where('nickname', 'like', "%{$q}%");
        }

        $productNames = $prodQuery->distinct()->orderBy('product_name')->pluck('product_name')->values();
        $nicknames = $nickQuery->distinct()->orderBy('nickname')->pluck('nickname')->values();

        $filtersPayload = [
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
        ];

        return response()->json([
            'success'          => true,
            'role'             => 'admin',
            'filter'           => 'all',
            'filters'          => $filtersPayload,
            'product_names'    => $productNames,
            'design_nicknames' => $nicknames,
            'nicknames'        => $nicknames,
            'data'             => $filtersPayload,
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: Product names list only for this craftsman folder (Admin Only).
     */
    public function productNames(Request $request, Craftsman $craftsman): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images details are accessible to Admin only.',
            ], 403);
        }

        $productNames = $this->getCraftsmanProductNames($craftsman);

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $productNames = $productNames->filter(fn ($item) => stripos((string) $item, $q) !== false)->values();
        }

        return response()->json([
            'success'       => true,
            'role'          => 'admin',
            'craftsman'     => [
                'id'        => $craftsman->id,
                'name'      => $craftsman->name,
                'email'     => $craftsman->email,
                'mobile'    => $craftsman->mobile,
            ],
            'filter'        => 'product_names',
            'count'         => $productNames->count(),
            'total'         => $productNames->count(),
            'product_names' => $productNames,
            'data'          => $productNames,
            'items'         => $productNames,
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: All product names across craftsmen (Admin Only).
     */
    public function allProductNames(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images directory is accessible to Admin only.',
            ], 403);
        }

        $query = DB::table('work_orders')
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where('product_name', 'like', "%{$q}%");
        }

        $productNames = $query->distinct()->orderBy('product_name')->pluck('product_name')->values();

        return response()->json([
            'success'       => true,
            'role'          => 'admin',
            'filter'        => 'product_names',
            'count'         => $productNames->count(),
            'total'         => $productNames->count(),
            'product_names' => $productNames,
            'data'          => $productNames,
            'items'         => $productNames,
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: Design nicknames list only for this craftsman folder (Admin Only).
     */
    public function nicknames(Request $request, Craftsman $craftsman): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images details are accessible to Admin only.',
            ], 403);
        }

        $nicknames = $this->getCraftsmanNicknames($craftsman);

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $nicknames = $nicknames->filter(fn ($item) => stripos((string) $item, $q) !== false)->values();
        }

        return response()->json([
            'success'          => true,
            'role'             => 'admin',
            'craftsman'        => [
                'id'           => $craftsman->id,
                'name'         => $craftsman->name,
                'email'        => $craftsman->email,
                'mobile'       => $craftsman->mobile,
            ],
            'filter'           => 'nicknames',
            'count'            => $nicknames->count(),
            'total'            => $nicknames->count(),
            'nicknames'        => $nicknames,
            'design_nicknames' => $nicknames,
            'data'             => $nicknames,
            'items'            => $nicknames,
        ]);
    }

    /**
     * Dedicated Endpoint or Filter: All design nicknames across craftsmen (Admin Only).
     */
    public function allNicknames(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'role'    => 'craftsman',
                'message' => 'Access denied. Craftsman images directory is accessible to Admin only.',
            ], 403);
        }

        $query = DB::table('design_codes')
            ->whereNotNull('nickname')
            ->where('nickname', '!=', '');

        if ($request->filled('q') || $request->filled('search')) {
            $q = trim((string) ($request->q ?? $request->search));
            $query->where('nickname', 'like', "%{$q}%");
        }

        $nicknames = $query->distinct()->orderBy('nickname')->pluck('nickname')->values();

        return response()->json([
            'success'          => true,
            'role'             => 'admin',
            'filter'           => 'nicknames',
            'count'            => $nicknames->count(),
            'total'            => $nicknames->count(),
            'nicknames'        => $nicknames,
            'design_nicknames' => $nicknames,
            'data'             => $nicknames,
            'items'            => $nicknames,
        ]);
    }

    /**
     * Retrieve available product names associated with this craftsman and their assigned design codes.
     */
    protected function getCraftsmanProductNames(Craftsman $craftsman)
    {
        $productNames = DB::table('work_orders')
            ->where(function ($q) use ($craftsman) {
                $q->where('craftsman_id', $craftsman->id)
                  ->orWhereIn('design_code', function ($sub) use ($craftsman) {
                      $sub->select('design_codes.code')
                          ->from('design_codes')
                          ->join('craftsman_design_code', 'design_codes.id', '=', 'craftsman_design_code.design_code_id')
                          ->where('craftsman_design_code.craftsman_id', $craftsman->id)
                          ->whereNotNull('design_codes.code');
                  });
            })
            ->whereNotNull('product_name')
            ->where('product_name', '!=', '')
            ->distinct()
            ->orderBy('product_name')
            ->pluck('product_name')
            ->values();

        if ($productNames->isEmpty()) {
            $productNames = DB::table('work_orders')
                ->whereNotNull('product_name')
                ->where('product_name', '!=', '')
                ->distinct()
                ->orderBy('product_name')
                ->pluck('product_name')
                ->values();
        }

        return $productNames;
    }

    /**
     * Retrieve available design nicknames associated with this craftsman.
     */
    protected function getCraftsmanNicknames(Craftsman $craftsman)
    {
        $craftsmanNicknames = $craftsman->designCodes()
            ->whereNotNull('design_codes.nickname')
            ->where('design_codes.nickname', '!=', '')
            ->pluck('design_codes.nickname');

        $woNicknames = DB::table('work_orders')
            ->where(function ($q) use ($craftsman) {
                $q->where('craftsman_id', $craftsman->id)
                  ->orWhereIn('design_code', function ($sub) use ($craftsman) {
                      $sub->select('design_codes.code')
                          ->from('design_codes')
                          ->join('craftsman_design_code', 'design_codes.id', '=', 'craftsman_design_code.design_code_id')
                          ->where('craftsman_design_code.craftsman_id', $craftsman->id)
                          ->whereNotNull('design_codes.code');
                  });
            })
            ->whereNotNull('design_nickname')
            ->where('design_nickname', '!=', '')
            ->pluck('design_nickname');

        $nicknames = $craftsmanNicknames->merge($woNicknames)
            ->filter(fn ($n) => !empty($n) && trim($n) !== '')
            ->map(fn ($n) => trim($n))
            ->unique()
            ->sort()
            ->values();

        if ($nicknames->isEmpty()) {
            $nicknames = DB::table('design_codes')
                ->whereNotNull('nickname')
                ->where('nickname', '!=', '')
                ->distinct()
                ->orderBy('nickname')
                ->pluck('nickname')
                ->values();
        }

        return $nicknames;
    }

    /**
     * Check if request asks for all/both filters (product names and design nicknames together).
     */
    protected function isAllFiltersRequest(Request $request): bool
    {
        $filterVal = strtolower(trim((string) (
            $request->input('filters')
            ?? $request->input('filter')
            ?? $request->input('type')
            ?? $request->input('get')
            ?? ''
        )));

        if (in_array($filterVal, ['all', 'both', '1', 'true', 'filters', 'options', 'list'], true)) {
            return true;
        }

        if ($request->has('filters') && empty($filterVal)) {
            return true;
        }

        return false;
    }

    /**
     * Check if request asks for product names filter only.
     */
    protected function isProductNamesFilterRequest(Request $request): bool
    {
        $filterVal = strtolower(trim((string) (
            $request->input('filters')
            ?? $request->input('filter')
            ?? $request->input('type')
            ?? $request->input('get')
            ?? ''
        )));

        if (in_array($filterVal, ['product_name', 'product_names', 'product', 'products'], true)) {
            return true;
        }

        if ($request->has('product_names') || $request->has('product_name')) {
            $val = strtolower(trim((string) ($request->input('product_names') ?? $request->input('product_name'))));
            if ($val === '' || in_array($val, ['1', 'true', 'all', 'yes', 'list', 'filter', 'options'], true) || $request->has('q')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request asks for nicknames filter only.
     */
    protected function isNicknamesFilterRequest(Request $request): bool
    {
        $filterVal = strtolower(trim((string) (
            $request->input('filters')
            ?? $request->input('filter')
            ?? $request->input('type')
            ?? $request->input('get')
            ?? ''
        )));

        if (in_array($filterVal, ['nickname', 'nicknames', 'design_nickname', 'design_nicknames', 'design-nicknames', 'design-nickname'], true)) {
            return true;
        }

        if ($request->has('nicknames') || $request->has('design_nicknames')) {
            $val = strtolower(trim((string) ($request->input('nicknames') ?? $request->input('design_nicknames'))));
            if ($val === '' || in_array($val, ['1', 'true', 'all', 'yes', 'list', 'filter', 'options'], true) || $request->has('q')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve current page supporting page, current_page, currentPage, page_no, pageNumber, p.
     */
    protected function resolvePage(Request $request, int $default = 1): int
    {
        $val = $request->input('page')
            ?? $request->input('current_page')
            ?? $request->input('currentPage')
            ?? $request->input('page_no')
            ?? $request->input('pageNumber')
            ?? $request->input('p')
            ?? $default;

        return max((int) $val, 1);
    }

    /**
     * Resolve per-page limit supporting per_page, limit, perPage, pageSize, rowsPerPage, take with bounds check.
     */
    protected function resolvePerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $val = $request->input('per_page')
            ?? $request->input('limit')
            ?? $request->input('perPage')
            ?? $request->input('pageSize')
            ?? $request->input('per-page')
            ?? $request->input('rowsPerPage')
            ?? $request->input('take')
            ?? $default;

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
}
