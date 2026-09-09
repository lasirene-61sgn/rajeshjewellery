<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CraftsmanController extends Controller
{
    /**
     * Auto-sync any design code typed in Work Orders into design_codes master table.
     */
    protected function syncWorkOrderDesignCodes(): void
    {
        $workOrderCodes = WorkOrder::whereNotNull('design_code')
            ->select('design_code', 'design_nickname')
            ->distinct()
            ->get();

        foreach ($workOrderCodes as $wo) {
            $code = trim($wo->design_code);
            if (!empty($code)) {
                DesignCode::firstOrCreate(
                    ['code' => $code],
                    ['nickname' => $wo->design_nickname ?: $code]
                );
            }
        }
    }

    public function index(Request $request): View
    {
        $query = Craftsman::with('designCodes')->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('designCodes', function ($sub) use ($search) {
                      $sub->where('code', 'like', "%{$search}%")
                          ->orWhere('nickname', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('design_code')) {
            $query->whereHas('designCodes', function ($q) use ($request) {
                $q->where('code', $request->design_code);
            });
        }

        $craftsmen = $query->paginate(15)->withQueryString();
        $designCodes = DesignCode::orderBy('code')->get();

        return view('admin.craftsmen.index', compact('craftsmen', 'designCodes'));
    }

    public function show(Craftsman $craftsman): View
    {
        $craftsman->load(['designCodes', 'workOrders' => function ($q) {
            $q->latest()->take(10);
        }]);

        return view('admin.craftsmen.show', compact('craftsman'));
    }

    public function create(): View
    {
        $this->syncWorkOrderDesignCodes();
        $designCodes = DesignCode::orderBy('code')->get();

        return view('admin.craftsmen.create', compact('designCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', 'unique:craftsmen,email'],
            'mobile'              => ['required', 'string', 'unique:craftsmen,mobile'],
            'password'            => ['required', 'string', 'min:6'],
            'is_active'           => ['nullable', 'boolean'],
            'design_codes'        => ['nullable', 'array'],
            'design_codes.*'      => ['exists:design_codes,id'],
            'design_names'        => ['nullable', 'array'],
            'new_design_code'     => ['nullable', 'string', 'max:50'],
            'new_design_nickname' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $craftsman = Craftsman::create($validated);

        $assignedIds = $request->input('design_codes', []);
        $designNames = $request->input('design_names', []);

        // Update the master design nicknames if edited in the form
        foreach ($assignedIds as $id) {
            if (!empty($designNames[$id])) {
                DesignCode::where('id', $id)->update([
                    'nickname' => trim($designNames[$id]),
                ]);
            }
        }

        // Create new design code if typed inline
        if ($request->filled('new_design_code')) {
            $newDesign = DesignCode::firstOrCreate(
                ['code' => trim($request->new_design_code)],
                ['nickname' => $request->new_design_nickname ?: trim($request->new_design_code)]
            );
            $assignedIds[] = $newDesign->id;
        }

        $craftsman->designCodes()->sync(array_unique($assignedIds));

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman created successfully.');
    }

    public function edit(Craftsman $craftsman): View
    {
        $this->syncWorkOrderDesignCodes();
        $craftsman->load('designCodes');
        $designCodes = DesignCode::orderBy('code')->get();

        return view('admin.craftsmen.edit', compact('craftsman', 'designCodes'));
    }

    public function update(Request $request, Craftsman $craftsman): RedirectResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email', Rule::unique('craftsmen', 'email')->ignore($craftsman->id)],
            'mobile'              => ['required', 'string', Rule::unique('craftsmen', 'mobile')->ignore($craftsman->id)],
            'password'            => ['nullable', 'string', 'min:6'],
            'is_active'           => ['nullable', 'boolean'],
            'design_codes'        => ['nullable', 'array'],
            'design_codes.*'      => ['exists:design_codes,id'],
            'design_names'        => ['nullable', 'array'],
            'new_design_code'     => ['nullable', 'string', 'max:50'],
            'new_design_nickname' => ['nullable', 'string', 'max:100'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $craftsman->update($validated);

        $assignedIds = $request->input('design_codes', []);
        $designNames = $request->input('design_names', []);

        // Update the master nicknames if edited
        foreach ($assignedIds as $id) {
            if (!empty($designNames[$id])) {
                DesignCode::where('id', $id)->update([
                    'nickname' => trim($designNames[$id]),
                ]);
            }
        }

        // Add inline new design code if entered
        if ($request->filled('new_design_code')) {
            $newDesign = DesignCode::firstOrCreate(
                ['code' => trim($request->new_design_code)],
                ['nickname' => $request->new_design_nickname ?: trim($request->new_design_code)]
            );
            $assignedIds[] = $newDesign->id;
        }

        $craftsman->designCodes()->sync(array_unique($assignedIds));

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman updated successfully.');
    }

    public function destroy(Craftsman $craftsman): RedirectResponse
    {
        $craftsman->designCodes()->detach();
        $craftsman->delete();

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman deleted successfully.');
    }
}