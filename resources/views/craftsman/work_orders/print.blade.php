<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work Order Print Slip - Craftsman Portal</title>
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
                
                <!-- Image Box -->
                <div class="border-2 border-stone-800 rounded-xl overflow-hidden bg-stone-50 mb-6 flex items-center justify-center" style="height: 600px;">
                    @if($order->design_image)
                        <img src="{{ asset('storage/' . $order->design_image) }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-stone-400 font-bold text-sm">No Design Image Available</div>
                    @endif
                </div>

                <!-- Specifications Data Table (Matching Reference Layout) -->
                <div class="grid grid-cols-2 gap-y-3 gap-x-6 text-sm font-semibold text-stone-900 border-t-2 border-stone-800 pt-5">
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">SNo</span>
                        <span class="font-bold">: {{ $order->id }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Weight</span>
                        <span class="font-extrabold text-amber-700">: {{ number_format($order->target_weight, 3) }}g</span>
                    </div>

                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Design</span>
                        <span class="font-bold text-amber-600">: {{ $order->design_code }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Size</span>
                        <span class="font-bold">: {{ $order->size ?? 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Order No</span>
                        <span class="font-bold">: {{ $order->work_order_no }}</span>
                    </div>
                    <div class="flex justify-between border-b border-stone-200 pb-2">
                        <span class="text-stone-600">Qty</span>
                        <span class="font-bold">: {{ $order->quantity }}</span>
                    </div>

                    <div class="flex justify-between col-span-2 pt-1">
                        <span class="text-stone-600">Order Date</span>
                        <span class="font-bold">: {{ optional($order->created_at)->format('d-m-Y') }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mt-6 print:hidden">
        <button onclick="window.print()" class="px-6 py-2.5 bg-amber-600 text-white font-bold rounded-xl shadow-md hover:bg-amber-700">Print Slip(s)</button>
    </div>
</body>
</html>
