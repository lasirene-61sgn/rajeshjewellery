<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work Order Print Slip - Admin Panel</title>
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
                
                <!-- Main Jewelry Design Image Container (Border Removed) -->
                <div class="w-full flex-1 flex items-center justify-center p-2 overflow-hidden bg-stone-50 relative">
                    @if($order->design_image)
                        <img src="{{ asset('storage/' . $order->design_image) }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-stone-400 font-sans font-bold text-base">No Design Image Available</div>
                    @endif

                    @if($order->has_seal)
                        <div class="absolute top-4 left-4 bg-amber-500 text-white font-sans font-black text-sm px-3 py-1 rounded shadow uppercase tracking-wider border border-amber-600">
                            916 SEAL
                        </div>
                    @endif
                </div>

                <!-- Specifications Data Table -->
                <div class="w-full pt-4 text-base leading-relaxed text-black font-serif border-t-2 border-black">
                    <div class="grid grid-cols-2 gap-x-12 gap-y-2 pt-2">
                        
                        <!-- Left Column -->
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="font-bold w-32">SNo</span>
                                <span class="font-normal">: {{ $order->id }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-32">Design</span>
                                <span class="font-bold text-amber-600">: {{ $order->design_code }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-32">Order No</span>
                                <span class="font-normal">: {{ $order->reference_no }}</span>
                                <!-- @if($order->has_seal)
                                    <span class="ml-2 font-sans font-black text-xs bg-amber-100 text-amber-900 border border-amber-400 px-1.5 py-0.5 rounded">916 SEAL</span>
                                @endif -->
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-32">Order Date</span>
                                <span class="font-normal">: {{ $order->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="font-bold w-28">Weight</span>
                                <span class="font-extrabold text-amber-700">: {{ number_format($order->target_weight, 3) }} g</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-28">Size</span>
                                <span class="font-normal">: {{ $order->size ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold w-28">Qty</span>
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
        <button onclick="window.print()" class="px-8 py-3 bg-indigo-600 text-white font-sans font-bold text-sm rounded-xl shadow-md hover:bg-indigo-700">
            Print Slip(s)
        </button>
    </div>

</body>