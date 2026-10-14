<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work Order Print Slip - Craftsman Portal</title>
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
            .page-break { page-break-after: always; }
        }

        .a4-page {
            width: 210mm;
            height: 297mm;
            padding: 12mm;
            box-sizing: border-box;
            margin: auto;
            background: white;
        }
    </style>
</head>
<body class="bg-stone-100 text-stone-900 font-serif">
    
    @php
        $orders = isset($workOrders) ? $workOrders : [$workOrder];
    @endphp

    @foreach($orders as $index => $order)
        <div class="a4-page {{ count($orders) > 1 && !$loop->last ? 'page-break' : '' }}">
            <!-- Outer Page Border Container -->
            <div class="w-full h-full border-2 border-black p-4 flex flex-col justify-between">
                
                <!-- Main Jewelry Design Image Container -->
                <div class="w-full flex-1 flex items-center justify-center p-2 overflow-hidden">
                    @if($order->design_image)
                        <img src="{{ asset('storage/' . $order->design_image) }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-stone-400 font-sans font-bold text-base">No Design Image Available</div>
                    @endif
                </div>

                <!-- Bottom Details Grid -->
                <div class="w-full pt-4 text-base leading-relaxed text-black font-serif">
                    <div class="grid grid-cols-2 gap-x-12 gap-y-2">
                        
                        <!-- Left Column -->
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="font-bold w-28">SNo</span>
                                <span class="font-normal">: {{ $order->id }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-28">Design</span>
                                <span class="font-normal">: {{ $order->design_code }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-28">Order No</span>
                                <span class="font-normal">: {{ $order->reference_no }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-28">Order Date</span>
                                <span class="font-normal">: {{ $order->created_at->format('d-m-Y') }}</span>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="font-bold w-24">Weight</span>
                                <span class="font-normal">: {{ number_format($order->target_weight, 3) }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-24">Size</span>
                                <span class="font-normal">: {{ $order->size ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-24">Qty</span>
                                <span class="font-normal">: {{ $order->quantity }}</span>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    @endforeach

    <!-- On-screen Print Button -->
    <div class="text-center my-6 print:hidden">
        <button onclick="window.print()" class="px-8 py-3 bg-stone-900 text-white font-sans font-bold text-sm rounded-lg shadow-md hover:bg-stone-800">
            Print Slip(s)
        </button>
    </div>

</body>
</html>