<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DesignCodeController extends Controller
{
    /**
     * Display design codes that are not assigned to any craftsman.
     */
    public function unassigned(Request $request): View
    {
        $query = DesignCode::doesntHave('craftsmen')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        $designCodes = $query->paginate(15)->withQueryString();

        return view('admin.design_codes.unassigned', compact('designCodes'));
    }
}
