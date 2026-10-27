<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work Order Print Slip - Craftsman Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body { 
                print-color-adjust: exact; 
                -webkit-print-color-adjust: exact; 
                background: white !important; 
                margin: 0 !important;
                padding: 0 !important;
            }
            .page-break { 
                page-break-after: always; 
                break-after: page;
            }
        }

        .a4-page {
            width: 210mm;
            height: 297mm;
            padding: 12mm;
            box-sizing: border-box;
            margin: auto;
            background: white;
        }

        /* Forces browsers to print images with sharp contrast instead of blurry smoothing */
        .print-image-sharp {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            image-rendering: pixelated; /* fallback if extreme low-res */
            -ms-interpolation-mode: nearest-neighbor;
        }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 font-sans">
    
    @php
        $orders = isset($workOrders) ? $workOrders : [$workOrder];
    @endphp

    @foreach($orders as $index => $order)
        <div class="a4-page {{ count($orders) > 1 && !$loop->last ? 'page-break' : '' }}">
            <!-- Outer Page Border Container -->
            <div class="w-full h-full border-2 border-black p-4 flex flex-col justify-between">
                
                <!-- Main Jewelry Design Image Container -->
                <div class="w-full flex-1 flex items-center justify-center p-2 overflow-hidden bg-stone-50 relative">
                    @if($order->design_image)
                        <img 
                            src="{{ asset('storage/' . $order->design_image) }}" 
                            alt="Design {{ $order->design_code }}" 
                            class="w-full h-full object-contain print-image-sharp"
                            loading="eager"
                        >
                    @else
                        <div class="text-stone-400 font-bold text-base">No Design Image Available</div>
                    @endif

                    <!-- 916 Seal positioned at the bottom center of the image container -->
                    @if($order->has_seal)
                        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-red-600 text-white font-black text-xs px-4 py-1 rounded shadow uppercase tracking-wider border border-red-700">
                            916 SEAL
                        </div>
                    @endif
                </div>

                <!-- Specifications Data Table -->
                <div class="w-full pt-4 text-base leading-relaxed text-black border-t-2 border-black">
                    <div class="grid grid-cols-2 gap-x-12 gap-y-2 pt-1">
                        
                        <!-- Left Column -->
                        <div class="space-y-2">
                            <div class="flex items-start">
                                <span class="font-bold w-32 shrink-0">SNo</span>
                                <span class="font-bold">: {{ $order->id }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-32 shrink-0">Design</span>
                                <span class="font-bold">: {{ $order->design_code }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-32 shrink-0">Order No</span>
                                <span class="font-bold">: {{ $order->reference_no }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-32 shrink-0">Order Date</span>
                                <span class="font-bold">: {{ $order->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-2">
                            <div class="flex items-start">
                                <span class="font-bold w-28 shrink-0">Weight</span>
                                <span class="font-bold">: {{ number_format($order->target_weight, 3) }} g</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-28 shrink-0">Size</span>
                                <span class="font-bold">: {{ $order->size ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-28 shrink-0">Qty</span>
                                <span class="font-bold">: {{ $order->quantity }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="font-bold w-28 shrink-0">Due Date</span>
                                <span class="font-bold">: {{ isset($order->due_date) ? \Carbon\Carbon::parse($order->due_date)->format('d/m/Y') : '' }}</span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    @endforeach

    <!-- On-screen Print Button -->
    <div class="text-center my-6 print:hidden">
        <button onclick="window.print()" class="px-8 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl shadow-md hover:bg-indigo-700">
            Print Slip(s)
        </button>
    </div>

</body>
</html>