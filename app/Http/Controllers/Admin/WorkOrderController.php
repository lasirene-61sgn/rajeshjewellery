<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Craftsman;
use App\Models\DesignCode;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $query = WorkOrder::with('craftsman')->orderBy('id', 'desc');

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
                      $sub->select(\Illuminate\Support\Facades\DB::raw(1))
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
            $query->where('craftsman_id', $request->craftsman_id);
        }

        if ($request->filled('design_code')) {
            $query->where('design_code', $request->design_code);
        }

        if ($request->filled('nickname')) {
            $nickFilter = trim($request->nickname);
            $query->where(function ($q) use ($nickFilter) {
                $q->where('design_nickname', 'like', "%{$nickFilter}%")
                  ->orWhereExists(function ($sub) use ($nickFilter) {
                      $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('design_codes')
                          ->whereColumn('design_codes.code', 'work_orders.design_code')
                          ->where('design_codes.nickname', 'like', "%{$nickFilter}%");
                  });
            });
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

        $categories    = WorkOrder::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->sort()->values();
        $subcategories = WorkOrder::whereNotNull('subcategory')->where('subcategory', '!=', '')->distinct()->pluck('subcategory')->sort()->values();
        $nicknames     = DesignCode::whereNotNull('nickname')->where('nickname', '!=', '')->whereColumn('nickname', '!=', 'code')->orderBy('nickname')->pluck('nickname')->unique()->values();
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
            'category'        => ['nullable', 'string', 'max:100'],
            'subcategory'     => ['nullable', 'string', 'max:100'],
            'size'            => ['nullable', 'string', 'max:50'],
            'length'          => ['nullable', 'string', 'max:50'],
            'rhodium_polish'  => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'seal'            => ['nullable', 'string', 'max:50'],
            'target_weight'   => ['required', 'numeric', 'min:0.001'],
            'due_date'        => ['required', 'date'],
            'job_type'        => ['nullable', 'string'],
            'instructions'    => ['nullable', 'string'],
            'design_image'    => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('design_image')) {
            $file = $request->file('design_image');
            $filename = 'work_orders/' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $referenceNo = $validated['reference_no'] ?? '';
            $sealValue   = $validated['seal'] ?? '';

            if (stripos($referenceNo, 'ESO') !== false || !empty($sealValue)) {
                $filePath = $file->getRealPath();
                $info = @getimagesize($filePath);
                $mime = $info['mime'] ?? 'image/jpeg';

                $img = match($mime) {
                    'image/png'  => @imagecreatefrompng($filePath),
                    'image/webp' => @imagecreatefromwebp($filePath),
                    default      => @imagecreatefromjpeg($filePath)
                };

                if ($img) {
                    $x = 30;
                    $y = 30;
                    $text = ' 916 SEAL ';
                    
                    $scale = 3; 
                    
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
                    
                    $padding = 10;
                    $bgColor = imagecolorallocate($img, 245, 158, 11); 
                    imagefilledrectangle($img, $x - $padding, $y - $padding, $x + $scaledW + $padding, $y + $scaledH + $padding, $bgColor);
                    
                    imagecopyresampled($img, $smallImg, $x, $y, 0, 0, $scaledW, $scaledH, $textW, $textH);
                    imagedestroy($smallImg);

                    ob_start();
                    match($mime) {
                        'image/png'  => imagepng($img),
                        'image/webp' => imagewebp($img),
                        default      => imagejpeg($img, null, 90)
                    };
                    $imageData = ob_get_clean();
                    imagedestroy($img);

                    Storage::disk('public')->put($filename, $imageData);
                    $validated['design_image'] = $filename;
                } else {
                    $validated['design_image'] = $file->store('work_orders', 'public');
                }
            } else {
                $validated['design_image'] = $file->store('work_orders', 'public');
            }
        }

        $year = date('Y');
        $lastOrder = WorkOrder::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $lastOrder ? ((int) substr($lastOrder->work_order_no, -4)) + 1 : 1;
        $validated['work_order_no'] = 'WO-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        $validated['status'] = 'pending';

        // Auto-assign craftsman if design code is already linked to one
        if (!empty($validated['design_code'])) {
            $designCodeStr = trim($validated['design_code']);
            $designCodeModel = DesignCode::with('craftsmen')->where('code', $designCodeStr)->first();

            if ($designCodeModel && $designCodeModel->craftsmen->isNotEmpty()) {
                // Automatically assign to the first linked craftsman
                $validated['craftsman_id'] = $designCodeModel->craftsmen->first()->id;
                $validated['status'] = 'allocated';
                $validated['allocated_at'] = Carbon::now();
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
            'reference_no'    => ['nullable', 'string', 'max:100'],
            'product_name'    => ['required', 'string', 'max:255'],
            'design_code'     => ['required', 'string', 'max:50'],
            'design_nickname' => ['nullable', 'string', 'max:255'],
            'unit_type'       => ['required', 'string'],
            'quantity'        => ['required', 'integer', 'min:1'],
            'screw_type'      => ['nullable', 'string'],
            'category'        => ['nullable', 'string', 'max:100'],
            'subcategory'     => ['nullable', 'string', 'max:100'],
            'size'            => ['nullable', 'string', 'max:50'],
            'length'          => ['nullable', 'string', 'max:50'],
            'rhodium_polish'  => ['nullable', 'boolean'],
            'hallmark_purity' => ['required', 'string'],
            'seal'            => ['nullable', 'string', 'max:50'],
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

            $file = $request->file('design_image');
            $filename = 'work_orders/' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $referenceNo = $validated['reference_no'] ?? '';
            $sealValue   = $validated['seal'] ?? '';

            if (stripos($referenceNo, 'ESO') !== false || !empty($sealValue)) {
                $filePath = $file->getRealPath();
                $info = @getimagesize($filePath);
                $mime = $info['mime'] ?? 'image/jpeg';

                $img = match($mime) {
                    'image/png'  => @imagecreatefrompng($filePath),
                    'image/webp' => @imagecreatefromwebp($filePath),
                    default      => @imagecreatefromjpeg($filePath)
                };

                if ($img) {
                    $x = 30;
                    $y = 30;
                    $text = ' 916 SEAL ';
                    
                    $scale = 3; 
                    
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
                    
                    $padding = 10;
                    $bgColor = imagecolorallocate($img, 245, 158, 11); 
                    imagefilledrectangle($img, $x - $padding, $y - $padding, $x + $scaledW + $padding, $y + $scaledH + $padding, $bgColor);
                    
                    imagecopyresampled($img, $smallImg, $x, $y, 0, 0, $scaledW, $scaledH, $textW, $textH);
                    imagedestroy($smallImg);

                    ob_start();
                    match($mime) {
                        'image/png'  => imagepng($img),
                        'image/webp' => imagewebp($img),
                        default      => imagejpeg($img, null, 90)
                    };
                    $imageData = ob_get_clean();
                    imagedestroy($img);

                    Storage::disk('public')->put($filename, $imageData);
                    $validated['design_image'] = $filename;
                } else {
                    $validated['design_image'] = $file->store('work_orders', 'public');
                }
            } else {
                $validated['design_image'] = $file->store('work_orders', 'public');
            }
        }

        $validated['rhodium_polish'] = $request->boolean('rhodium_polish');

        if (!empty($validated['design_code'])) {
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
            'due_date'     => ['nullable', 'date'],
        ]);

        $updateData = [
            'craftsman_id' => $request->craftsman_id,
            'status'       => 'allocated',
            'allocated_at' => Carbon::now(),
        ];

        if ($request->filled('due_date')) {
            $updateData['due_date'] = $request->due_date;
        }

        WorkOrder::whereIn('id', $request->order_ids)->update($updateData);

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

    public function bulkComplete(Request $request): RedirectResponse
    {
        $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id']
        ]);

        WorkOrder::whereIn('id', $request->order_ids)->update([
            'status'       => 'completed',
            'approved_at'  => Carbon::now(),
        ]);

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
            'order_ids'   => ['required', 'array'],
            'order_ids.*' => ['exists:work_orders,id'],
        ]);

        $workOrders = WorkOrder::with('craftsman')->whereIn('id', $request->order_ids)->get();

        return view('admin.work_orders.print', compact('workOrders'));
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
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20240'],
            'due_date_days' => ['required', 'integer', 'between:4,7'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $firstLine = fgets($handle);
            rewind($handle);

            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
            $header = fgetcsv($handle, 1000, $delimiter);

            $normalizedHeader = $header ? array_map(function ($h) {
                return strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\x9F\xEF\xBB\xBF]/u', '', $h)));
            }, $header) : [];

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!empty($normalizedHeader) && count($normalizedHeader) === count($row)) {
                    $rows[] = array_combine($normalizedHeader, $row);
                }
            }
            fclose($handle);
        }

        $importedCount = 0;
        $skippedCount = 0;
        $year = date('Y');

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

            $referenceNo  = $getVal(['order no', 'orderno', 'ref no', 'reference no']);

            if (!empty($referenceNo) && WorkOrder::where('reference_no', $referenceNo)->exists()) {
                $skippedCount++;
                continue;
            }

            $unitType     = $getVal(['order type', 'ordertype', 'unit', 'unit type']) ?? 'pcs';
            $orderDateStr = $getVal(['order date', 'orderdate', 'date']);
            $productName  = $getVal(['product', 'product name', 'item', 'item name']) ?? 'Unknown Product';
            $designCode   = $getVal(['design', 'design code', 'code', 'designcode']) ?? 'DEFAULT';

            $excelNickname = $getVal(['nickname', 'design nickname', 'design_nickname', 'alias']);
            $seal          = $getVal(['seal', '916 seal', 'hallmark seal', 'seal type']) ?? (stripos($referenceNo ?? '', 'ESO') !== false ? '916' : null);
            $weight        = $getVal(['weight', 'target weight', 'wt']);
            $size          = $getVal(['size']);
            $quantity      = $getVal(['quantity', 'qty', 'pcs']);
            $jobType       = $getVal(['balance', 'job type', 'jobtype']);
            $instructions  = $getVal(['remarks', 'instruction', 'instructions', 'note']);

            $parsedDate = now();
            if (!empty($orderDateStr)) {
                try {
                    $parsedDate = Carbon::createFromFormat('d/m/Y', trim($orderDateStr));
                } catch (\Exception $e) {
                    try {
                        $parsedDate = Carbon::parse($orderDateStr);
                    } catch (\Exception $ex) {
                        $parsedDate = now();
                    }
                }
            }

            // Automatically set Due Date to the requested days after TODAY (not the old order date)
            $dueDateDays = (int) $request->due_date_days;
            $dueDate = now()->addDays($dueDateDays);

            $lastOrder = WorkOrder::whereYear('created_at', $year)->latest('id')->first();
            $nextNumber = $lastOrder ? ((int) substr($lastOrder->work_order_no, -4)) + 1 : ($importedCount + 1);
            $workOrderNo = 'WO-' . $year . '_' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $designCodeStr = !empty($designCode) ? $designCode : 'DEFAULT';

            $existingDesign = DesignCode::where('code', $designCodeStr)->first();

            if (!empty($excelNickname)) {
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

            if (!empty($excelNickname) && $existingDesign) {
                $existingDesign->update(['nickname' => $excelNickname]);
            }

            // Check if design code is mapped to a craftsman for auto-allocation
            $craftsmanId = null;
            $status = 'pending';
            $designCodeModel = DesignCode::with('craftsmen')->where('code', $designCodeStr)->first();

            if ($designCodeModel && $designCodeModel->craftsmen->isNotEmpty()) {
                $craftsmanId = $designCodeModel->craftsmen->first()->id;
                $status = 'in_process'; // Automatically shifts to in_process as requested
            }

            WorkOrder::create([
                'work_order_no'   => $workOrderNo,
                'reference_no'    => $referenceNo,
                'product_name'    => $productName,
                'design_code'     => $designCodeStr,
                'design_nickname' => $nickname,
                'unit_type'       => $unitType,
                'quantity'        => is_numeric($quantity) ? (int) $quantity : 1,
                'category'        => null,
                'size'            => $size,
                'hallmark_purity' => null,
                'seal'            => $seal,
                'target_weight'   => is_numeric($weight) ? (float) $weight : 0.000,
                'job_type'        => $jobType,
                'instructions'    => $instructions,
                'craftsman_id'    => $craftsmanId,
                'status'          => $status,
                'due_date'        => $dueDate, // Populated automatically 7 days past order date
                'allocated_at'    => $craftsmanId ? Carbon::now() : null,
                'created_at'      => $parsedDate,
            ]);
            $importedCount++;
        }

        $backUrl = session('admin_work_orders_url', route('admin.work_orders.index'));
        return redirect($backUrl)->with('success', "Successfully imported {$importedCount} work orders with calculated due dates. ({$skippedCount} duplicates skipped).");
    }
}