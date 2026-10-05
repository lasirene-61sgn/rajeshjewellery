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
    public function index(Request $request): View
    {
        $query = DesignCode::with('craftsmen')->withCount('craftsmen')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        $designCodes = $query->paginate(15)->withQueryString();
        $craftsmen = \App\Models\Craftsman::orderBy('name')->get();

        return view('admin.design_codes.index', compact('designCodes', 'craftsmen'));
    }

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

    public function update(Request $request, DesignCode $designCode): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:100'],
            'image'    => ['nullable', 'image', 'mimes:jpg,jpeg,svg,png,gif,webp', 'max:5048'],
            'craftsmen_ids' => ['nullable', 'array'],
            'craftsmen_ids.*' => ['exists:craftsmen,id'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('design_codes', 'public');
        }

        $designCode->update([
            'nickname' => $validated['nickname'] ?? null,
        ]);
        
        if (isset($validated['image'])) {
            $designCode->update(['image' => $validated['image']]);
        }

        $designCode->craftsmen()->sync($request->input('craftsmen_ids', []));

        return redirect()->back()->with('success', 'Design Code updated successfully.');
    }

    public function destroy(DesignCode $designCode): \Illuminate\Http\RedirectResponse
    {
        $designCode->craftsmen()->detach();
        $designCode->delete();

        return redirect()->back()->with('success', 'Design Code deleted successfully.');
    }
}
