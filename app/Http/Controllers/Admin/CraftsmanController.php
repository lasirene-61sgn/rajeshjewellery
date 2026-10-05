<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
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
                // Only set nickname if the work order has a real nickname (not equal to the code)
                $realNickname = (!empty($wo->design_nickname) && $wo->design_nickname !== $code)
                    ? $wo->design_nickname
                    : null;

                DesignCode::firstOrCreate(
                    ['code' => $code],
                    ['nickname' => $realNickname]
                );
            }
        }
    }

    protected function retroactivelyUpdateWorkOrders(Craftsman $craftsman, array $assignedIds): void
    {
        if (empty($assignedIds)) return;

        $designCodes = DesignCode::whereIn('id', $assignedIds)->get();

        foreach ($designCodes as $design) {
            $codeStr = $design->code;

            // 1. Copy image to all work orders with this code that lack an image
            if ($design->image) {
                WorkOrder::where('design_code', $codeStr)
                    ->where(function($q) {
                        $q->whereNull('design_image')->orWhere('design_image', '');
                    })
                    ->update(['design_image' => $design->image]);
            }

            // 2. Auto-assign all pending work orders with this code to this craftsman
            WorkOrder::where('design_code', $codeStr)
                ->where('status', 'pending')
                ->update([
                    'craftsman_id' => $craftsman->id,
                    'status'       => 'in_process',
                    'allocated_at' => Carbon::now(),
                ]);
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
        $designCodes = DesignCode::doesntHave('craftsmen')->orderBy('code')->get();

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
            'new_design_nickname' => ['nullable', 'string', 'max:100'],
            'design_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,svg,png,gif,webp', 'max:5048'],
            'existing_design_images'   => ['nullable', 'array'],
            'existing_design_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,svg,png,gif,webp', 'max:5048'],
            'delete_design_images'     => ['nullable', 'array'],
        ]);

        $validated['plain_password'] = $validated['password'];
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

        // Handle deleting existing images
        if ($request->filled('delete_design_images')) {
            foreach ($request->delete_design_images as $id) {
                if (in_array($id, $assignedIds)) {
                    DesignCode::where('id', $id)->update(['image' => null]);
                }
            }
        }

        // Handle uploading new images for existing codes
        if ($request->hasFile('existing_design_images')) {
            foreach ($request->file('existing_design_images') as $id => $file) {
                if (in_array($id, $assignedIds) && $file) {
                    $path = $file->store('design_codes', 'public');
                    DesignCode::where('id', $id)->update(['image' => $path]);
                }
            }
        }

        // Create new design code if typed inline
        if ($request->filled('new_design_code')) {
            $codeStr = trim($request->new_design_code);
            $nickStr = $request->filled('new_design_nickname') ? trim($request->new_design_nickname) : null;
            // Don't use the code itself as nickname
            if ($nickStr === $codeStr) {
                $nickStr = null;
            }
            $newDesign = DesignCode::firstOrCreate(
                ['code' => $codeStr],
                ['nickname' => $nickStr]
            );
            // If a real nickname was provided and the design already existed, update it
            if ($nickStr && $newDesign->wasRecentlyCreated === false) {
                $newDesign->update(['nickname' => $nickStr]);
            }
            
            if ($request->hasFile('design_image')) {
                 $path = $request->file('design_image')->store('design_codes', 'public');
                 $newDesign->update(['image' => $path]);
            }

            $assignedIds[] = $newDesign->id;
        }

        $craftsman->designCodes()->sync(array_unique($assignedIds));
        
        $this->retroactivelyUpdateWorkOrders($craftsman, $assignedIds);

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman created successfully.');
    }

    public function edit(Craftsman $craftsman): View
    {
        $this->syncWorkOrderDesignCodes();
        $craftsman->load('designCodes');
        $designCodes = DesignCode::whereHas('craftsmen', function($q) use ($craftsman) {
            $q->where('craftsman_id', $craftsman->id);
        })->orDoesntHave('craftsmen')->orderBy('code')->get();

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
            'design_image'        => ['nullable', 'image', 'mimes:jpg,jpeg,svg,png,gif,webp', 'max:5048'],
            'existing_design_images'   => ['nullable', 'array'],
            'existing_design_images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,svg,png,gif,webp', 'max:5048'],
            'delete_design_images'     => ['nullable', 'array'],
        ]);

        if (!empty($validated['password'])) {
            $validated['plain_password'] = $validated['password'];
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

        // Handle deleting existing images
        if ($request->filled('delete_design_images')) {
            foreach ($request->delete_design_images as $id) {
                if (in_array($id, $assignedIds)) {
                    DesignCode::where('id', $id)->update(['image' => null]);
                }
            }
        }

        // Handle uploading new images for existing codes
        if ($request->hasFile('existing_design_images')) {
            foreach ($request->file('existing_design_images') as $id => $file) {
                if (in_array($id, $assignedIds) && $file) {
                    $path = $file->store('design_codes', 'public');
                    DesignCode::where('id', $id)->update(['image' => $path]);
                }
            }
        }

        // Add inline new design code if entered
        if ($request->filled('new_design_code')) {
            $codeStr = trim($request->new_design_code);
            $nickStr = $request->filled('new_design_nickname') ? trim($request->new_design_nickname) : null;
            // Don't use the code itself as nickname
            if ($nickStr === $codeStr) {
                $nickStr = null;
            }
            $newDesign = DesignCode::firstOrCreate(
                ['code' => $codeStr],
                ['nickname' => $nickStr]
            );
            // If a real nickname was provided and the design already existed, update it
            if ($nickStr && $newDesign->wasRecentlyCreated === false) {
                $newDesign->update(['nickname' => $nickStr]);
            }

            if ($request->hasFile('design_image')) {
                 $path = $request->file('design_image')->store('design_codes', 'public');
                 $newDesign->update(['image' => $path]);
            }

            $assignedIds[] = $newDesign->id;
        }

        $craftsman->designCodes()->sync(array_unique($assignedIds));

        $this->retroactivelyUpdateWorkOrders($craftsman, $assignedIds);

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman updated successfully.');
    }

    public function destroy(Craftsman $craftsman): RedirectResponse
    {
        $craftsman->designCodes()->detach();
        $craftsman->delete();

        return redirect()->route('admin.craftsmen.index')->with('success', 'Craftsman deleted successfully.');
    }

    public function autoAssign(Craftsman $craftsman): RedirectResponse
    {
        $designCodes = $craftsman->designCodes()->pluck('code')->toArray();
        
        if (empty($designCodes)) {
            return redirect()->back()->withErrors(['error' => 'Craftsman has no design codes assigned.']);
        }

        $pendingOrders = WorkOrder::whereIn('design_code', $designCodes)
            ->where('status', 'pending')
            ->get();

        $allocatedCount = $pendingOrders->count();
        $missingImagesCount = $pendingOrders->whereNull('design_image')->count();

        if ($allocatedCount > 0) {
            WorkOrder::whereIn('id', $pendingOrders->pluck('id'))->update([
                'craftsman_id' => $craftsman->id,
                'status' => 'in_process',
                'allocated_at' => Carbon::now(),
            ]);
        }

        return redirect()->back()->with('success', "{$allocatedCount} orders have been allocated and sent to the In Process tab. {$missingImagesCount} images are not added.");
    }
}