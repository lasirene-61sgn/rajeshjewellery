<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card - {{ $workOrder->work_order_no }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 20px; color: #111; }
        .card { border: 2px solid #000; padding: 20px; max-width: 750px; margin: auto; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 14px; }
        .box { border: 1px solid #ccc; padding: 8px; }
        .label { font-size: 11px; text-transform: uppercase; color: #555; }
        .val { font-weight: bold; font-size: 14px; margin-top: 2px; }
        .image-box { text-align: center; margin-bottom: 15px; }
        .image-box img { max-height: 180px; max-width: 100%; border: 1px solid #ddd; }
        @media print { button { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="card">
        <div class="header">
            <div>
                <h2 style="margin:0;">JEWELRY WORKSHOP JOB CARD</h2>
                <span style="font-size: 12px;">Order Date: {{ $workOrder->created_at->format('d/m/Y') }}</span>
            </div>
            <div style="text-align: right;">
                <h2 style="margin:0; font-family: monospace;">{{ $workOrder->work_order_no }}</h2>
                <span style="font-size: 12px;">Due: {{ $workOrder->due_date?->format('d/m/Y') }}</span>
            </div>
        </div>

        @if($workOrder->design_image)
            <div class="image-box">
                <img src="{{ asset('storage/' . $workOrder->design_image) }}" alt="Design">
            </div>
        @endif

        <div class="grid">
            <div class="box"><div class="label">Product Name</div><div class="val">{{ $workOrder->product_name }}</div></div>
            <div class="box"><div class="label">Design Code</div><div class="val">{{ $workOrder->design_code }}</div></div>
            <div class="box"><div class="label">Category</div><div class="val">{{ $workOrder->category }} ({{ $workOrder->subcategory ?? 'General' }})</div></div>
            <div class="box"><div class="label">Purity / Hallmark</div><div class="val">{{ $workOrder->hallmark_purity }}</div></div>
            <div class="box"><div class="label">Target Weight</div><div class="val">{{ number_format($workOrder->target_weight, 3) }} g</div></div>
            <div class="box"><div class="label">Quantity / Units</div><div class="val">{{ $workOrder->quantity }} {{ $workOrder->unit_type }}</div></div>
            <div class="box"><div class="label">Assigned Craftsman</div><div class="val">{{ $workOrder->craftsman->name ?? 'Unassigned' }}</div></div>
            <div class="box"><div class="label">Rhodium Polish</div><div class="val">{{ $workOrder->rhodium_polish ? 'YES' : 'NO' }}</div></div>
        </div>

        @if($workOrder->instructions)
            <div class="box" style="margin-bottom: 20px;">
                <div class="label">Specific Workshop Instructions</div>
                <div class="val" style="font-size: 12px; font-weight: normal; margin-top: 4px;">{{ $workOrder->instructions }}</div>
            </div>
        @endif

        <div style="margin-top: 40px; display: flex; justify-content: space-between; font-size: 12px;">
            <div>Craftsman Signature: __________________</div>
            <div>QC Approval: __________________</div>
        </div>
    </div>

</body>
</html>