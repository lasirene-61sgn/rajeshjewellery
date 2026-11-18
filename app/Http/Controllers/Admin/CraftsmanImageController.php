<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use Illuminate\Http\Request;
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
                  ->orWhere('email', 'like', "%{$search}%");
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

        $query = $craftsman->designCodes();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('design_codes.code', 'like', "%{$search}%")
                  ->orWhere('design_codes.nickname', 'like', "%{$search}%");
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

        $designCodes = $query->orderBy('design_codes.code')->paginate(16)->withQueryString();

        return view('admin.craftsman_images.show', compact('craftsman', 'designCodes', 'stats'));
    }
}
