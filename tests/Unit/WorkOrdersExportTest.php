<?php

namespace Tests\Unit;

use App\Exports\WorkOrdersExport;
use App\Models\WorkOrder;
use PHPUnit\Framework\TestCase;

class WorkOrdersExportTest extends TestCase
{
    public function test_export_headings(): void
    {
        $export = new WorkOrdersExport(collect([]));

        $expectedHeadings = [
            'Reference No',
            'Order No',
            'Design Code',
            'Weight',
            'Length',
            'Qty',
        ];

        $this->assertSame($expectedHeadings, $export->headings());
    }

    public function test_export_mapping(): void
    {
        $order = new WorkOrder([
            'reference_no' => 'REF-12345',
            'work_order_no' => 'WO-99999',
            'design_code' => 'DC-ABC',
            'target_weight' => '12.345',
            'size' => '18cm',
            'quantity' => 3,
        ]);

        $export = new WorkOrdersExport(collect([$order]));
        $mapped = $export->map($order);

        $this->assertSame('REF-12345', $mapped[0]);
        $this->assertSame('WO-99999', $mapped[1]);
        $this->assertSame('DC-ABC', $mapped[2]);
        $this->assertSame(12.345, $mapped[3]);
        $this->assertSame('18cm', $mapped[4]);
        $this->assertSame(3, $mapped[5]);
    }

    public function test_export_mapping_with_null_and_fallback_values(): void
    {
        $order = new WorkOrder([
            'reference_no' => null,
            'work_order_no' => null,
            'design_code' => null,
            'target_weight' => null,
            'length' => null,
            'size' => null,
            'quantity' => null,
        ]);

        $export = new WorkOrdersExport(collect([$order]));
        $mapped = $export->map($order);

        $this->assertSame('-', $mapped[0]);
        $this->assertSame('-', $mapped[1]);
        $this->assertSame('-', $mapped[2]);
        $this->assertNull($mapped[3]);
        $this->assertSame('-', $mapped[4]);
        $this->assertNull($mapped[5]);
    }
}
