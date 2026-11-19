<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CraftsmanImageController extends Controller
{
    /**
     * Display a listing of craftsmen as folders.
     */
    public function index(Request $request): View
    {
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

        $craftsmen = $query->orderBy('name')->paginate(12)->withQueryString();

        $totalCraftsmen = Craftsman::count();
        $totalDesignCodes = DesignCode::count();
        $totalImages = DesignCode::whereNotNull('image')->where('image', '!=', '')->count();

        return view('admin.craftsman_images.index', compact(
            'craftsmen',
            'totalCraftsmen',
            'totalDesignCodes',
            'totalImages'
        ));
    }

    /**
     * Display the design codes and images assigned to a specific craftsman.
     */
    public function show(Request $request, Craftsman $craftsman): View
    {
        $stats = [
            'total' => $craftsman->designCodes()->count(),
            'with_images' => $craftsman->designCodes()->whereNotNull('design_codes.image')->where('design_codes.image', '!=', '')->count(),
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

        $designCodes = $query->orderBy('design_codes.code')->paginate(16)->withQueryString();

        // Product names associated with this craftsman and their assigned design codes
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

        return view('admin.craftsman_images.show', compact('craftsman', 'designCodes', 'stats', 'productNames'));
    }
}
