<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Craftsman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CraftsmanController extends Controller
{
    /**
     * View-only listing of craftsmen (Admin Only).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Craftsmen directory is accessible to Admin only.',
            ], 403);
        }

        $query = Craftsman::with('designCodes')
            ->withCount([
                'workOrders as total_work_orders',
                'workOrders as active_work_orders' => fn ($q) => $q->whereIn('status', ['allocated', 'in_process', 'returned']),
                'workOrders as completed_work_orders' => fn ($q) => $q->where('status', 'completed'),
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' || $request->status === '1');
        }

        if ($request->filled('design_code')) {
            $query->whereHas('designCodes', function ($q) use ($request) {
                $q->where('code', $request->design_code);
            });
        }

        $perPage = $this->resolvePerPage($request, 15);
        $paginator = $query->latest('id')->paginate($perPage)->withQueryString();

        $items = $paginator->getCollection()->map(fn (Craftsman $c) => [
            'id'                    => $c->id,
            'name'                  => $c->name,
            'email'                 => $c->email,
            'mobile'                => $c->mobile,
            'is_active'             => (bool) $c->is_active,
            'assigned_designs_count'=> $c->designCodes->count(),
            'assigned_design_codes' => $c->designCodes->pluck('code')->values(),
            'total_work_orders'     => (int) $c->total_work_orders,
            'active_work_orders'    => (int) $c->active_work_orders,
            'completed_work_orders' => (int) $c->completed_work_orders,
            'created_at'            => $c->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
        ]);

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => [
                'items'      => $items,
                'pagination' => $this->formatPagination($paginator),
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
     * View-only details of a craftsman (Admin Only).
     */
    public function show(Request $request, Craftsman $craftsman): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Craftsman details are accessible to Admin only.',
            ], 403);
        }

        $craftsman->load([
            'designCodes',
            'workOrders' => fn ($q) => $q->latest()->take(10),
            'orders'     => fn ($q) => $q->latest()->take(10),
        ]);

        return response()->json([
            'success' => true,
            'role'    => 'admin',
            'data'    => [
                'id'             => $craftsman->id,
                'name'           => $craftsman->name,
                'email'          => $craftsman->email,
                'mobile'         => $craftsman->mobile,
                'is_active'      => (bool) $craftsman->is_active,
                'design_codes'   => $craftsman->designCodes->map(fn ($d) => [
                    'id'       => $d->id,
                    'code'     => $d->code,
                    'nickname' => $d->nickname,
                    'image_url'=> $d->image ? url('storage/' . $d->image) : null,
                ]),
                'recent_work_orders' => $craftsman->workOrders->map(fn ($wo) => [
                    'id'            => $wo->id,
                    'work_order_no' => $wo->work_order_no,
                    'reference_no'  => $wo->reference_no,
                    'product_name'  => $wo->product_name,
                    'status'        => $wo->status,
                    'due_date'      => $wo->due_date?->format('d M Y'),
                ]),
                'recent_customer_orders' => $craftsman->orders->map(fn ($o) => [
                    'id'            => $o->id,
                    'order_no'      => $o->order_no,
                    'customer_name' => $o->customer_name,
                    'status'        => $o->status,
                    'due_date'      => $o->due_date?->format('d M Y'),
                ]),
                'created_at'     => $craftsman->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A'),
            ],
        ]);
    }
}

