<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WorkOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\UploadedFile;

class WorkOrderController extends Controller
{


    /**
     * Process, enhance resolution, stamp seal, and save jewelry design image.
     */
    protected function processAndSaveDesignImage(UploadedFile $file, string $referenceNo = '', string $sealValue = ''): string
    {
        $filePath = $file->getRealPath();
        $info = @getimagesize($filePath);
        $mime = $info['mime'] ?? 'image/jpeg';
        $origW = $info[0] ?? 0;
        $origH = $info[1] ?? 0;

        $src = match ($mime) {
            'image/png' => @imagecreatefrompng($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => @imagecreatefromjpeg($filePath)
        };

        if (! $src || $origW === 0 || $origH === 0) {
            return $file->store('work_orders', 'public');
        }

        // 1. UPSCALE FOR A4 PRINT: Ensure width is at least 1800px (standard crisp print size)
        $targetW = max($origW, 1800);
        $targetH = (int) round(($origH / $origW) * $targetW);

        $canvas = imagecreatetruecolor($targetW, $targetH);

        // Retain PNG/WebP transparency if present
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $white);
        }

        // High quality resample
        imagecopyresampled($canvas, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
        imagedestroy($src);

        // 2. ENHANCE / SHARPEN: Apply unsharp convolution matrix for crisp jewelry contours
        $sharpenMatrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1]
        ];
        $divisor = 8;
        $offset = 0;
        imageconvolution($canvas, $sharpenMatrix, $divisor, $offset);

        // 3. APPLY 916 SEAL (if ESO in reference or seal requested)
        if (stripos($referenceNo, 'ESO') !== false || ! empty($sealValue)) {
            $x = 40;
            $y = 40;
            $text = ' 916 SEAL ';
            $scale = 4; // Scaled up proportionally for 1800px+ high-res image

            $font = 5;
            $fontWidth = imagefontwidth($font);
            $fontHeight = imagefontheight($font);

            $textW = strlen($text) * $fontWidth;
            $textH = $fontHeight;

            $scaledW = $textW * $scale;
            $scaledH = $textH * $scale;

            $smallImg = imagecreatetruecolor($textW, $textH);
            $bgColorSmall = imagecolorallocate($smallImg, 245, 158, 11);
            $textColorSmall = imagecolorallocate($smallImg, 255, 255, 255);

            imagefill($smallImg, 0, 0, $bgColorSmall);
            imagestring($smallImg, $font, 0, 0, $text, $textColorSmall);

            $padding = 15;
            $bgColor = imagecolorallocate($canvas, 245, 158, 11);
            imagefilledrectangle($canvas, $x - $padding, $y - $padding, $x + $scaledW + $padding, $y + $scaledH + $padding, $bgColor);

            imagecopyresampled($canvas, $smallImg, $x, $y, 0, 0, $scaledW, $scaledH, $textW, $textH);
            imagedestroy($smallImg);
        }

        // 4. SAVE AT HIGH PRINT QUALITY (95% quality)
        $filename = 'work_orders/' . uniqid() . '.jpg';
        ob_start();
        imagejpeg($canvas, null, 95);
        $imageData = ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('public')->put($filename, $imageData);

        return $filename;
    }


    public function index(Request $request): View
    {
        $currentTab = $request->query('tab', 'all');
        $perPage = (int) $request->query('per_page', 15);
        $allowedPerPage = [10, 15, 25, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $query = WorkOrder::with('craftsman')->orderBy('created_at', 'desc')->orderBy('id', 'desc');

        // 1. Search Query
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('work_order_no', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('design_code', 'like', "%{$search}%")
                    ->orWhere('design_nickname', 'like', "%{$search}%")
                    ->orWhere('seal', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select(DB::raw(1))
                            ->from('design_codes')
                            ->whereColumn('design_codes.code', 'work_orders.design_code')
                            ->where('design_codes.nickname', 'like', "%{$search}%");
                    });
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
            $craftsmanId = $request->craftsman_id;
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
            $query->where('product_name', $request->product_name);
        }

        $selectedOrderTypes = $request->input('order_type');
        if (! empty($selectedOrderTypes)) {
            if (is_string($selectedOrderTypes)) {
                $selectedOrderTypes = array_values(array_filter(array_map('trim', explode(',', $selectedOrderTypes))));
            } elseif (is_array($selectedOrderTypes)) {
                $selectedOrderTypes = array_values(array_filter(array_map('trim', $selectedOrderTypes)));
            } else {
                $selectedOrderTypes = [];
            }
            if (! empty($selectedOrderTypes)) {
                $query->whereIn('order_type', $selectedOrderTypes);
            }
        } else {
            $selectedOrderTypes = [];
        }

        if ($request->filled('nickname')) {
            $nickFilter = trim($request->nickname);
            $query->where(function ($q) use ($nickFilter) {
                $q->where('design_nickname', 'like', "%{$nickFilter}%")
                    ->orWhereExists(function ($sub) use ($nickFilter) {
                        $sub->select(DB::raw(1))
                            ->from('design_codes')
                            ->whereColumn('design_codes.code', 'work_orders.design_code')
                            ->where('design_codes.nickname', 'like', "%{$nickFilter}%");
                    });
            });
        }

        // 3. Date Filters (Filtered by Created Date)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Clone the query with all search/dropdown filters applied BEFORE applying the status tab filter
        $baseQuery = clone $query;

        // 4. Tab Status Filtering
        match ($currentTab) {
            'pending' => $query->where('status', 'pending'),
            'allocated' => $query->where('status', 'allocated'),
            'in_process' => $query->where('status', 'in_process'),
            'partially_completed' => $query->where('status', 'partially_completed'),
            'returned' => $query->where('status', 'returned'),
            'for_approval' => $query->where('status', 'for_approval'),
            'completed' => $query->where('status', 'completed'),
            'overdue' => $query->where('status', '!=', 'completed')->whereDate('due_date', '<', Carbon::today()),
            default => null,
        };

        // Tab Badges Counts
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'allocated' => (clone $baseQuery)->where('status', 'allocated')->count(),
            'in_process' => (clone $baseQuery)->where('status', 'in_process')->count(),
            'partially_completed' => (clone $baseQuery)->where('status', 'partially_completed')->count(),
            'returned' => (clone $baseQuery)->where('status', 'returned')->count(),
            'for_approval' => (clone $baseQuery)->where('status', 'for_approval')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'overdue' => (clone $baseQuery)->where('status', '!=', 'completed')->whereDate('due_date', '<', Carbon::today())->count(),
        ];

        $categories = WorkOrder::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $subcategories = WorkOrder::whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->pluck('subcategory')->sort()->values();
        $nicknames = DesignCode::whereNotNull('nickname')->where('nickname', '!=', '')->whereColumn('nickname', '!=', 'code')->orderBy('nickname')->pluck('nickname')->unique()->values();
        $orderTypes = WorkOrder::whereNotNull('order_type')->where('order_type', '!=', '')->distinct()->pluck('order_type')->sort()->values();
        $craftsmen = Craftsman::orderBy('name')->get();
        $designCodes = DesignCode::with('craftsmen')->orderBy('code')->get();
        $productNames = WorkOrder::whereNotNull('product_name')->where('product_name', '!=', '')->distinct()->pluck('product_name')->sort()->values();

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
            'orderTypes',
            'selectedOrderTypes',
            'craftsmen',
            'designCodes',
            'productNames'
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
            'reference_no' => ['nullable', 'string', 'max:100'],
            'product_name' => ['required', 'string', 'max:255'],
            'design_code' => ['required', 'string', 'max:50'],
            'design_nickname' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'screw_type' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:50'],
            'length' => ['nullable', 'string', 'max:50'],
            'rhodium_polish' => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'seal' => ['nullable', 'string', 'max:50'],
            'target_weight' => ['required', 'numeric', 'min:0.001'],
            'due_date' => ['required', 'date'],
            'job_type' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'design_image' => ['nullable', 'image', 'max:4096'],
            'order_type' => ['nullable', 'string'],
        ]);

        // Enhanced Image Processing
        if ($request->hasFile('design_image')) {
            $validated['design_image'] = $this->processAndSaveDesignImage(
                $request->file('design_image'),
                $validated['reference_no'] ?? '',
                $validated['seal'] ?? ''
            );
        }

        $year = date('Y');
        $lastOrder = WorkOrder::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $lastOrder ? ((int) substr($lastOrder->work_order_no, -4)) + 1 : 1;
        $validated['work_order_no'] = 'WO-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $validated['status'] = 'pending';

        // Auto-assign craftsman if design code is already linked to one
        if (! empty($validated['design_code'])) {
            $designCodeStr = trim($validated['design_code']);
            $designCodeModel = DesignCode::with('craftsmen')->where('code', $designCodeStr)->first();

            if ($designCodeModel) {
                // If the design code has an image, copy it if the work order doesn't have one
                if (empty($validated['design_image']) && $designCodeModel->image) {
                    $validated['design_image'] = $designCodeModel->image;
                }
            }

            DesignCode::firstOrCreate(
                ['code' => $designCodeStr],
                ['nickname' => $validated['design_nickname'] ?? null]
            );
        }

        WorkOrder::create($validated);

        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));

        return redirect($backUrl)->with('success', 'Work order created successfully and auto-allocated if mapped.');
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
            'reference_no' => ['nullable', 'string', 'max:100'],
            'product_name' => ['required', 'string', 'max:255'],
            'design_code' => ['required', 'string', 'max:50'],
            'design_nickname' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'completed_quantity' => ['nullable', 'integer', 'min:0'],
            'screw_type' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'size' => ['nullable', 'string', 'max:50'],
            'length' => ['nullable', 'string', 'max:50'],
            'rhodium_polish' => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'seal' => ['nullable', 'string', 'max:50'],
            'target_weight' => ['required', 'numeric', 'min:0.001'],
            'due_date' => ['required', 'date'],
            'job_type' => ['nullable', 'string', 'max:100'],
            'instructions' => ['nullable', 'string'],
            'design_image' => ['nullable', 'image', 'max:4096'],
            'order_type' => ['nullable', 'string'],
        ]);

        // Enhanced Image Processing
        if ($request->hasFile('design_image')) {
            if ($workOrder->design_image) {
                Storage::disk('public')->delete($workOrder->design_image);
            }

            $validated['design_image'] = $this->processAndSaveDesignImage(
                $request->file('design_image'),
                $validated['reference_no'] ?? '',
                $validated['seal'] ?? ''
            );
        }

        $validated['rhodium_polish'] = $request->boolean('rhodium_polish');

        if (! empty($validated['design_code'])) {
            $designCodeStr = trim($validated['design_code']);
            $existingDesign = DesignCode::where('code', $designCodeStr)->first();

            if (empty($validated['design_nickname'] ?? null) && $existingDesign && $existingDesign->nickname && $existingDesign->nickname !== $existingDesign->code) {
                $validated['design_nickname'] = $existingDesign->nickname;
            }

            DesignCode::firstOrCreate(
                ['code' => $designCodeStr],
                ['nickname' => $validated['design_nickname'] ?? null]
            );
        }

        if ($request->has('completed_quantity')) {
            $completed = (int) $request->completed_quantity;
            $totalQty = (int) ($validated['quantity'] ?? $workOrder->quantity);
            $validated['completed_quantity'] = $completed;
            if ($completed >= $totalQty && $totalQty > 0) {
                $validated['status'] = 'completed';
                $validated['approved_at'] = Carbon::now();
            } elseif ($completed > 0) {
                $validated['status'] = 'partially_completed';
            }
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

        $status = 'allocated';
        $designCodeModel = DesignCode::with('craftsmen')->where('code', $workOrder->design_code)->first();
        if ($designCodeModel && $designCodeModel->craftsmen->contains('id', $request->craftsman_id)) {
            $status = 'in_process';
        }

        $workOrder->update([
            'craftsman_id' => $request->craftsman_id,
            'status' => $status,
            'allocated_at' => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} allocated to craftsman.");
    }

    public function bulkAllocate(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
            'craftsman_id' => ['required', 'exists:craftsmen,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $orders = WorkOrder::whereIn('id', $request->order_ids)->get();

        foreach ($orders as $workOrder) {
            $status = 'allocated';
            $designCodeModel = DesignCode::with('craftsmen')->where('code', $workOrder->design_code)->first();
            if ($designCodeModel && $designCodeModel->craftsmen->contains('id', $request->craftsman_id)) {
                $status = 'in_process';
            }

            $updateData = [
                'craftsman_id' => $request->craftsman_id,
                'status' => $status,
                'allocated_at' => Carbon::now(),
            ];

            if ($request->filled('due_date')) {
                $updateData['due_date'] = $request->due_date;
            }

            $workOrder->update($updateData);
        }

        return back()->with('success', count($request->order_ids) . ' work orders successfully allocated.');
    }

    public function undoAllocation(WorkOrder $workOrder): RedirectResponse
    {
        if ($workOrder->status !== 'allocated') {
            return back()->withErrors(['error' => 'Cannot undo allocation once work has started.']);
        }

        $workOrder->update([
            'craftsman_id' => null,
            'status' => 'pending',
            'allocated_at' => null,
        ]);

        return back()->with('success', "Allocation cleared for {$workOrder->work_order_no}.");
    }

    public function returnOrder(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $request->validate([
            'return_due_date' => ['required', 'date', 'after_or_equal:today'],
            'return_reason' => ['required', 'string'],
            'return_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data = [
            'status' => 'returned',
            'return_due_date' => $request->return_due_date,
            'return_reason' => $request->return_reason,
            'return_count' => ($workOrder->return_count ?? 0) + 1,
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
            'completed_quantity' => $workOrder->quantity,
            'status' => 'completed',
            'approved_at' => Carbon::now(),
        ]);

        return back()->with('success', "Order {$workOrder->work_order_no} fully approved and marked completed ({$workOrder->quantity}/{$workOrder->quantity}).");
    }

    public function updateQuantity(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $totalQty = (int) $workOrder->quantity;

        $validated = $request->validate([
            'completed_quantity' => ['required', 'integer', 'min:0', 'max:' . max(1, $totalQty)],
        ]);

        $completed = (int) $validated['completed_quantity'];

        if ($completed >= $totalQty && $totalQty > 0) {
            $workOrder->update([
                'completed_quantity' => $totalQty,
                'status' => 'completed',
                'approved_at' => Carbon::now(),
            ]);

            return back()->with('success', "Order {$workOrder->work_order_no} fully completed ({$totalQty}/{$totalQty}).");
        } elseif ($completed > 0) {
            $workOrder->update([
                'completed_quantity' => $completed,
                'status' => 'partially_completed',
            ]);

            $balance = $totalQty - $completed;
            return back()->with('success', "Order {$workOrder->work_order_no} updated: {$completed} of {$totalQty} completed ({$balance} balance remaining).");
        } else {
            $status = $workOrder->craftsman_id ? 'in_process' : 'pending';
            $workOrder->update([
                'completed_quantity' => 0,
                'status' => $status,
            ]);

            return back()->with('success', "Order {$workOrder->work_order_no} completed quantity reset to 0.");
        }
    }

    public function bulkComplete(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $orders = WorkOrder::whereIn('id', $request->order_ids)->get();
        foreach ($orders as $order) {
            $order->update([
                'completed_quantity' => $order->quantity,
                'status' => 'completed',
                'approved_at' => Carbon::now(),
            ]);
        }

        return back()->with('success', count($request->order_ids) . ' work orders marked as completed.');
    }

    public function print(WorkOrder $workOrder): View
    {
        $workOrder->load('craftsman');
        $workOrders = collect([$workOrder]);

        return view('admin.work_orders.print', compact('workOrders', 'workOrder'));
    }

    public function bulkPrint(Request $request): View
    {
        $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $workOrders = WorkOrder::with('craftsman')->whereIn('id', $request->order_ids)->get();

        return view('admin.work_orders.print', compact('workOrders'));
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        // 1. If specific order IDs are selected (checkbox selection)
        if ($request->has('order_ids') && ! empty($request->order_ids)) {
            $orderIds = is_array($request->order_ids) ? $request->order_ids : explode(',', (string) $request->order_ids);
            $orderIds = array_filter(array_map('trim', $orderIds));

            $orders = WorkOrder::whereIn('id', $orderIds)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $fileName = 'work_orders_selected_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        } else {
            // 2. Otherwise apply tab and filter toolbar parameters
            $currentTab = $request->input('tab', 'all');
            $query = WorkOrder::query()->orderBy('created_at', 'desc')->orderBy('id', 'desc');

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('work_order_no', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('design_code', 'like', "%{$search}%")
                        ->orWhere('design_nickname', 'like', "%{$search}%")
                        ->orWhere('seal', 'like', "%{$search}%")
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select(DB::raw(1))
                                ->from('design_codes')
                                ->whereColumn('design_codes.code', 'work_orders.design_code')
                                ->where('design_codes.nickname', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('subcategory')) {
                $query->where('subcategory', $request->subcategory);
            }

            if ($request->filled('craftsman_id')) {
                $craftsmanId = $request->craftsman_id;
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
                $query->where('product_name', $request->product_name);
            }

            $selectedOrderTypes = $request->input('order_type');
            if (! empty($selectedOrderTypes)) {
                if (is_string($selectedOrderTypes)) {
                    $selectedOrderTypes = array_values(array_filter(array_map('trim', explode(',', $selectedOrderTypes))));
                } elseif (is_array($selectedOrderTypes)) {
                    $selectedOrderTypes = array_values(array_filter(array_map('trim', $selectedOrderTypes)));
                } else {
                    $selectedOrderTypes = [];
                }
                if (! empty($selectedOrderTypes)) {
                    $query->whereIn('order_type', $selectedOrderTypes);
                }
            }

            if ($request->filled('nickname')) {
                $nickFilter = trim($request->nickname);
                $query->where(function ($q) use ($nickFilter) {
                    $q->where('design_nickname', 'like', "%{$nickFilter}%")
                        ->orWhereExists(function ($sub) use ($nickFilter) {
                            $sub->select(DB::raw(1))
                                ->from('design_codes')
                                ->whereColumn('design_codes.code', 'work_orders.design_code')
                                ->where('design_codes.nickname', 'like', "%{$nickFilter}%");
                        });
                });
            }

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            match ($currentTab) {
                'pending' => $query->where('status', 'pending'),
                'allocated' => $query->where('status', 'allocated'),
                'in_process' => $query->where('status', 'in_process'),
                'returned' => $query->where('status', 'returned'),
                'for_approval' => $query->where('status', 'for_approval'),
                'completed' => $query->where('status', 'completed'),
                'overdue' => $query->where('status', '!=', 'completed')->whereDate('due_date', '<', Carbon::today()),
                default => null,
            };

            $orders = $query->get();
            $fileName = 'work_orders_' . $currentTab . '_' . Carbon::now()->format('Y-m-d_His') . '.xlsx';
        }

        if ($orders->isEmpty()) {
            return back()->with('error', 'No orders found to export.');
        }

        return Excel::download(new WorkOrdersExport($orders), $fileName);
    }

    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        $workOrder->delete();

        return redirect()->route('admin.work_orders.index')->with('success', 'Work order deleted.');
    }

    public function importForm(): View
    {
        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));

        return view('admin.work_orders.import', compact('backUrl'));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods,xlsm,xlsb', 'max:20240'],
            'due_date_days' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $extension = $file->getClientOriginalExtension();

        $rows = [];

        if (in_array(strtolower($extension), ['csv', 'txt'])) {
            if (($handle = fopen($path, 'r')) !== false) {
                $firstLine = fgets($handle);
                rewind($handle);

                $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
                $header = fgetcsv($handle, 1000, $delimiter);

                $normalizedHeader = $header ? array_map(function ($h) {
                    return strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xEF\xBB\xBF]/u', '', (string) $h)));
                }, $header) : [];

                while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                    if (! empty($normalizedHeader) && count($normalizedHeader) === count($row)) {
                        $rows[] = array_combine($normalizedHeader, $row);
                    }
                }
                fclose($handle);
            }
        } else {
            // Excel files
            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();

            // Format data so dates appear as text instead of floats
            $data = $worksheet->toArray(null, true, true, false);

            if (count($data) > 0) {
                $header = array_shift($data);

                $validIndices = [];
                $normalizedHeader = [];
                foreach ($header as $index => $h) {
                    if ($h !== null && trim((string) $h) !== '') {
                        $validIndices[] = $index;
                        $normalizedHeader[] = strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xEF\xBB\xBF]/u', '', (string) $h)));
                    }
                }

                foreach ($data as $row) {
                    $isEmptyRow = true;
                    foreach ($row as $cell) {
                        if ($cell !== null && trim((string) $cell) !== '') {
                            $isEmptyRow = false;
                            break;
                        }
                    }
                    if ($isEmptyRow) {
                        continue;
                    }

                    $filteredRow = [];
                    foreach ($validIndices as $index) {
                        $filteredRow[] = $row[$index] ?? null;
                    }

                    if (count($normalizedHeader) === count($filteredRow)) {
                        $rows[] = array_combine($normalizedHeader, $filteredRow);
                    }
                }
            }
        }

        $importedCount = 0;
        $skippedCount = 0;
        $year = date('Y');

        $parseCustomDate = function ($dateVal) {
            if (empty($dateVal)) {
                return null;
            }
            $trimmed = trim((string) $dateVal);
            // Check if it's an Excel numeric date serial
            if (is_numeric($trimmed) && (float) $trimmed > 25000 && (float) $trimmed < 65000) {
                try {
                    return Carbon::instance(Date::excelToDateTimeObject((float) $trimmed));
                } catch (\Throwable $e) {
                }
            }
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y', 'd/m/y', 'd-m-y', 'd/m/Y H:i', 'd-m-Y H:i', 'Y-m-d H:i:s', 'd/m/Y H:i:s'] as $format) {
                try {
                    return Carbon::createFromFormat($format, $trimmed);
                } catch (\Throwable $e) {
                }
            }
            try {
                return Carbon::parse($trimmed);
            } catch (\Throwable $e) {
                return null;
            }
        };

        foreach ($rows as $cleanRow) {
            $getVal = function (array $keys) use ($cleanRow) {
                foreach ($keys as $key) {
                    $lowerKey = strtolower(trim($key));
                    if (array_key_exists($lowerKey, $cleanRow)) {
                        $val = trim($cleanRow[$lowerKey]);

                        return $val !== '' ? $val : null;
                    }
                }

                return null;
            };

            $referenceNo = $getVal(['order no', 'orderno', 'ref no', 'reference no']);

            if (! empty($referenceNo) && WorkOrder::where('reference_no', $referenceNo)->exists()) {
                $skippedCount++;

                continue;
            }

            $unitType = $getVal(['unit', 'unit type', 'unit_type']) ?? 'pcs';
            $orderDateStr = $getVal(['order date', 'orderdate', 'date', 'order_date', 'created at', 'created_at']);
            $expectedDateStr = $getVal(['expected date', 'expected_date', 'expecteddate', 'exp date', 'exp. date', 'exp_date', 'due date', 'due_date', 'duedate', 'expected delivery date', 'delivery date']);
            $productName = $getVal(['Product', 'product name', 'item', 'item name']) ?? 'Unknown Product';
            $designCode = $getVal(['Design', 'design code', 'code', 'designcode']) ?? 'DEFAULT';

            $excelNickname = $getVal(['nickname', 'design nickname', 'design_nickname', 'alias']);
            $seal = $getVal(['seal', '916 seal', 'hallmark seal', 'seal type']) ?? (stripos($referenceNo ?? '', 'ESO') !== false ? '916' : null);
            $weight = $getVal(['Weight', 'target weight', 'wt']);
            $size = $getVal(['Size']);
            $quantity = $getVal(['Quantity', 'qty', 'pcs']);
            $jobType = $getVal(['balance', 'job type', 'jobtype']);
            $instructions = $getVal(['remarks', 'instruction', 'instructions', 'note']);
            $orderType = $getVal(['Order Type', 'order_type', 'ordertype']);

            // Order Date becomes Created Date
            $parsedCreatedDate = $parseCustomDate($orderDateStr) ?? now();

            // Expected Date becomes Due Date for every order
            $parsedExpectedDate = $parseCustomDate($expectedDateStr);
            $dueDateDays = (int) ($request->due_date_days ?? 7);

            if ($parsedExpectedDate) {
                $dueDate = $parsedExpectedDate->toDateString();
            } else {
                $dueDate = $parsedCreatedDate->copy()->addDays($dueDateDays)->toDateString();
            }

            $lastOrder = WorkOrder::whereYear('created_at', $year)->latest('id')->first();
            $nextNumber = $lastOrder ? ((int) substr(str_replace('_', '-', $lastOrder->work_order_no), -4)) + 1 : ($importedCount + 1);
            $workOrderNo = 'WO-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $designCodeStr = ! empty($designCode) ? $designCode : 'DEFAULT';

            $existingDesign = DesignCode::where('code', $designCodeStr)->first();

            if (! empty($excelNickname)) {
                $nickname = $excelNickname;
            } elseif ($existingDesign && $existingDesign->nickname && $existingDesign->nickname !== $existingDesign->code) {
                $nickname = $existingDesign->nickname;
            } else {
                $nickname = null;
            }

            DesignCode::firstOrCreate(
                ['code' => $designCodeStr],
                ['nickname' => $nickname]
            );

            if (! empty($excelNickname) && $existingDesign) {
                $existingDesign->update(['nickname' => $excelNickname]);
            }

            // Check if design code is mapped to a craftsman for auto-allocation
            $craftsmanId = null;
            $status = 'pending';
            $imagePath = null;
            $designCodeModel = DesignCode::with('craftsmen')->where('code', $designCodeStr)->first();

            if ($designCodeModel) {
                if ($designCodeModel->image) {
                    $imagePath = $designCodeModel->image;
                }
            }

            $workOrder = WorkOrder::create([
                'work_order_no' => $workOrderNo,
                'reference_no' => $referenceNo,
                'product_name' => $productName,
                'design_code' => $designCodeStr,
                'design_nickname' => $nickname,
                'unit_type' => $unitType,
                'quantity' => is_numeric($quantity) ? (int) $quantity : 1,
                'category' => null,
                'size' => $size,
                'hallmark_purity' => null,
                'seal' => $seal,
                'target_weight' => is_numeric($weight) ? (float) $weight : 0.000,
                'job_type' => $jobType,
                'instructions' => $instructions,
                'craftsman_id' => $craftsmanId,
                'status' => $status,
                'due_date' => $dueDate,
                'allocated_at' => $craftsmanId ? Carbon::now() : null,
                'created_at' => $parsedCreatedDate,
                'order_type' => $orderType,
                'design_image' => $imagePath,
            ]);

            if ($parsedCreatedDate && $workOrder->created_at != $parsedCreatedDate) {
                $workOrder->created_at = $parsedCreatedDate;
                $workOrder->saveQuietly();
            }

            $importedCount++;
        }

        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));

        return redirect($backUrl)->with('success', "Successfully imported {$importedCount} work orders. ({$skippedCount} duplicates skipped).");
    }
}
