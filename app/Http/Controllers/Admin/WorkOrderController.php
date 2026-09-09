<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        $currentTab = $request->query('tab', 'all');
        $perPage = (int) $request->query('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $query = WorkOrder::with('craftsman')->latest();

        // 1. Search Query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('design_code', 'like', "%{$search}%")
                  ->orWhere('design_nickname', 'like', "%{$search}%");
            });
        }

        // 2. Dropdown Filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        if ($request->filled('craftsman_id')) {
            $query->where('craftsman_id', $request->craftsman_id);
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
        }

        if ($request->filled('nickname')) {
            $query->where('design_nickname', 'like', "%" . trim($request->nickname) . "%");
        }

        // 3. Date Filters
        if ($request->filled('from_date')) {
            $query->whereDate('due_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('due_date', '<=', $request->to_date);
        }

        // 4. Tab Status Filtering
        match ($currentTab) {
            'pending'      => $query->where('status', 'pending'),
            'allocated'    => $query->where('status', 'allocated'),
            'in_process'   => $query->whereIn('status', ['in_process', 'returned']),
            'for_approval' => $query->where('status', 'for_approval'),
            'completed'    => $query->where('status', 'completed'),
            'overdue'      => $query->where('status', '!=', 'completed')->whereDate('due_date', '<', Carbon::today()),
            default        => null,
        };

        // Tab Badges Counts
        $counts = [
            'all'          => WorkOrder::count(),
            'pending'      => WorkOrder::where('status', 'pending')->count(),
            'allocated'    => WorkOrder::where('status', 'allocated')->count(),
            'in_process'   => WorkOrder::whereIn('status', ['in_process', 'returned'])->count(),
            'for_approval' => WorkOrder::where('status', 'for_approval')->count(),
            'completed'    => WorkOrder::where('status', 'completed')->count(),
            'overdue'      => WorkOrder::where('status', '!=', 'completed')->whereDate('due_date', '<', Carbon::today())->count(),
        ];

        // Options for Filter Dropdowns
        $categories    = WorkOrder::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $subcategories = WorkOrder::whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->pluck('subcategory')->sort()->values();
        $nicknames     = WorkOrder::whereNotNull('design_nickname')->where('design_nickname', '!=', '')->distinct()->pluck('design_nickname')->sort()->values();
        $craftsmen     = Craftsman::orderBy('name')->get();
        $designCodes   = DesignCode::orderBy('code')->get();

        $workOrders = $query->paginate($perPage)->withQueryString();

        session(['admin_work_orders_url' => request()->fullUrl()]);

        return view('admin.work_orders.index', compact(
            'workOrders',
            'counts',
            'currentTab',
            'perPage',
            'allowedPerPage',
            'categories',
            'subcategories',
            'nicknames',
            'craftsmen',
            'designCodes'
        ));
    }

    public function create(): View
    {
        $designCodes = DesignCode::orderBy('code')->get();
        return view('admin.work_orders.create', compact('designCodes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no'    => ['nullable', 'string', 'max:100'],
            'product_name'    => ['required', 'string', 'max:255'],
            'design_code'     => ['required', 'string', 'max:50'],
            'design_nickname' => ['nullable', 'string', 'max:255'],
            'unit_type'       => ['required', 'string'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'screw_type'      => ['nullable', 'string'],
            'category'        => ['required', 'string', 'max:100'],
            'subcategory'     => ['nullable', 'string', 'max:100'],
            'size'            => ['nullable', 'string', 'max:50'],
            'length'          => ['nullable', 'string', 'max:50'],
            'rhodium_polish'  => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'target_weight'   => ['required', 'numeric', 'min:0.001'],
            'due_date'        => ['required', 'date'],
            'job_type'        => ['nullable', 'string'],
            'instructions'    => ['nullable', 'string'],
            'design_image'    => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('design_image')) {
            $validated['design_image'] = $request->file('design_image')->store('work_orders', 'public');
        }

        $year = date('Y');
        $lastOrder = WorkOrder::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $lastOrder ? ((int) substr($lastOrder->work_order_no, -4)) + 1 : 1;
        $validated['work_order_no'] = 'WO-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'pending';

        // Keep master table sync using nickname
        if (!empty($validated['design_code'])) {
            $designCodeStr = trim($validated['design_code']);
            $existingDesign = DesignCode::where('code', $designCodeStr)->first();
            
            if (empty($validated['design_nickname']) && $existingDesign && $existingDesign->nickname) {
                $validated['design_nickname'] = $existingDesign->nickname;
            }

            DesignCode::firstOrCreate(
                ['code' => $designCodeStr],
                ['nickname' => $validated['design_nickname'] ?: $designCodeStr]
            );
        }

        WorkOrder::create($validated);

        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));
        return redirect($backUrl)->with('success', 'Work order created successfully.');
    }

    public function show(Request $request, WorkOrder $workOrder): View
    {
        $workOrder->load('craftsman');
        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));

        return view('admin.work_orders.show', compact('workOrder', 'backUrl'));
    }

    public function edit(Request $request, WorkOrder $workOrder): View
    {
        $designCodes = DesignCode::orderBy('code')->get();
        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));

        return view('admin.work_orders.edit', compact('workOrder', 'designCodes', 'backUrl'));
    }

    public function update(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'reference_no'    => ['nullable', 'string', 'max:100'],
            'product_name'    => ['required', 'string', 'max:255'],
            'design_code'     => ['required', 'string', 'max:50'],
            'design_nickname' => ['nullable', 'string', 'max:255'],
            'unit_type'       => ['required', 'string'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'screw_type'      => ['nullable', 'string'],
            'category'        => ['required', 'string', 'max:100'],
            'subcategory'     => ['nullable', 'string', 'max:100'],
            'size'            => ['nullable', 'string', 'max:50'],
            'length'          => ['nullable', 'string', 'max:50'],
            'rhodium_polish'  => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'target_weight'   => ['required', 'numeric', 'min:0.001'],
            'due_date'        => ['required', 'date'],
            'job_type'        => ['nullable', 'string', 'max:100'],
            'instructions'    => ['nullable', 'string'],
            'design_image'    => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('design_image')) {
            if ($workOrder->design_image) {
                Storage::disk('public')->delete($workOrder->design_image);
            }
            $validated['design_image'] = $request->file('design_image')->store('designs', 'public');
        }

        $validated['rhodium_polish'] = $request->boolean('rhodium_polish');

        if (!empty($validated['design_code'])) {
            $designCodeStr = trim($validated['design_code']);
            $existingDesign = DesignCode::where('code', $designCodeStr)->first();
            
            if (empty($validated['design_nickname']) && $existingDesign && $existingDesign->nickname) {
                $validated['design_nickname'] = $existingDesign->nickname;
            }

            DesignCode::firstOrCreate(
                ['code' => $designCodeStr],
                ['nickname' => $validated['design_nickname'] ?: $designCodeStr]
            );
        }

        $workOrder->update($validated);

        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));
        return redirect($backUrl)->with('success', 'Work order updated successfully.');
    }

    public function allocate(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $request->validate([
            'craftsman_id' => ['required', 'exists:craftsmen,id'],
        ]);

        $workOrder->update([
            'craftsman_id' => $request->craftsman_id,
            'status'       => 'allocated',
            'allocated_at' => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} allocated to craftsman.");
    }

    public function bulkAllocate(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids'    => ['required', 'array'],
            'order_ids.*'  => ['exists:work_orders,id'],
            'craftsman_id' => ['required', 'exists:craftsmen,id'],
        ]);

        WorkOrder::whereIn('id', $request->order_ids)->update([
            'craftsman_id' => $request->craftsman_id,
            'status'       => 'allocated',
            'allocated_at' => Carbon::now(),
        ]);

        return back()->with('success', count($request->order_ids) . ' work orders successfully allocated.');
    }

    public function undoAllocation(WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->status !== 'allocated') {
            return back()->withErrors(['error' => 'Cannot undo allocation once work has started.']);
        }

        $workOrder->update([
            'craftsman_id' => null,
            'status'       => 'pending',
            'allocated_at' => null,
        ]);

        return back()->with('success', "Allocation cleared for {$workOrder->work_order_no}.");
    }

    public function returnOrder(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $request->validate([
            'return_due_date' => ['required', 'date', 'after_or_equal:today'],
            'return_reason'   => ['required', 'string'],
            'return_image'    => ['nullable', 'image', 'max:4096'],
        ]);

        $data = [
            'status'          => 'returned',
            'return_due_date' => $request->return_due_date,
            'return_reason'   => $request->return_reason,
            'return_count'    => ($workOrder->return_count ?? 0) + 1,
        ];

        if ($request->hasFile('return_image')) {
            $data['return_image'] = $request->file('return_image')->store('returns', 'public');
        }

        $workOrder->update($data);

        return back()->with('success', "Order {$workOrder->work_order_no} returned to craftsman for rework.");
    }

    public function approve(WorkOrder $workOrder): RedirectResponse
    {
        $workOrder->update([
            'status'       => 'completed',
            'approved_at'  => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} approved and marked completed.");
    }

    public function print(WorkOrder $workOrder): View
    {
        $workOrder->load('craftsman');
        return view('admin.work_orders.print', compact('workOrder'));
    }

    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $workOrder->delete();
        return redirect()->route('admin.work_orders.index')->with('success', 'Work order deleted.');
    }
}