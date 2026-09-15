<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work Order Print Slip - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; background: white !important; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 font-sans p-4 sm:p-8">
    <div class="max-w-3xl mx-auto space-y-8">
        @php
            $orders = isset($workOrders) ? $workOrders : [$workOrder];
        @endphp

        @foreach($orders as $index => $order)
            <div class="bg-white border-2 border-stone-800 rounded-2xl p-6 shadow-sm relative {{ count($orders) > 1 && !$loop->last ? 'page-break mb-8' : '' }}">
                
                <div class="flex justify-between items-center mb-4 border-b-2 border-stone-800 pb-2">
                    <div>
                        <h2 class="text-xl font-bold uppercase tracking-wide m-0">Jewelry Workshop Job Card</h2>
                        <span class="text-xs font-semibold text-stone-500">Created: {{ $order->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-mono font-bold m-0">{{ $order->work_order_no }}</h2>
                        <span class="text-xs font-semibold text-rose-700">Due: {{ $order->due_date?->format('d/m/Y') ?? 'N/A' }}</span>
                    </div>
                </div>

                <!-- Image Box -->
                <div class="border-2 border-stone-800 rounded-xl overflow-hidden bg-stone-50 mb-6 flex items-center justify-center" style="height: 600px;">
                    @if($order->design_image)
                        <img src="{{ asset('storage/' . $order->design_image) }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-stone-400 font-bold text-sm">No Design Image Available</div>
                    @endif
                </div>

                <!-- Specifications Data Table -->
                <div class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm font-semibold text-stone-900 border-t-2 border-stone-800 pt-5">
                    
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Product Name</span>
                        <span class="font-bold">: {{ $order->product_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Design Code</span>
                        <span class="font-bold text-amber-600">: {{ $order->design_code }}</span>
                    </div>

                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Category</span>
                        <span class="font-bold">: {{ $order->category }} ({{ $order->subcategory ?? 'General' }})</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Purity / Hallmark</span>
                        <span class="font-bold">: {{ $order->hallmark_purity }}</span>
                    </div>

                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Target Weight</span>
                        <span class="font-extrabold text-amber-700">: {{ number_format($order->target_weight, 3) }} g</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Quantity / Units</span>
                        <span class="font-bold">: {{ $order->quantity }} {{ $order->unit_type }}</span>
                    </div>

                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Assigned Craftsman</span>
                        <span class="font-bold text-indigo-700">: {{ $order->craftsman->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Rhodium Polish</span>
                        <span class="font-bold">: {{ $order->rhodium_polish ? 'YES' : 'NO' }}</span>
                    </div>

                </div>

                @if($order->instructions)
                    <div class="mt-4 p-4 bg-stone-50 border-2 border-stone-200 rounded-xl">
                        <div class="text-xs text-stone-500 uppercase font-bold mb-1">Specific Workshop Instructions</div>
                        <div class="text-sm font-medium">{{ $order->instructions }}</div>
                    </div>
                @endif

                <div class="mt-12 flex justify-between text-xs font-bold text-stone-600">
                    <div>Craftsman Signature: __________________</div>
                    <div>QC Approval: __________________</div>
                </div>

            </div>
        @endforeach
    </div>

    <div class="text-center mt-6 print:hidden">
        <button onclick="window.print()" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-md hover:bg-indigo-700">Print Slip(s)</button>
    </div>
</body>
</html>
