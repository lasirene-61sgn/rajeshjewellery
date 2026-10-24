<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class WorkOrderExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_selected_work_orders(): void
    {
        Excel::fake();

        $admin = Admin::factory()->create();

        $order1 = WorkOrder::factory()->create([
            'work_order_no' => 'WO-TEST-001',
            'reference_no'  => 'REF-TEST-001',
            'design_code'   => 'DC-001',
            'target_weight' => 15.500,
            'size'          => '18',
            'quantity'      => 2,
            'status'        => 'pending',
        ]);

        $order2 = WorkOrder::factory()->create([
            'work_order_no' => 'WO-TEST-002',
            'reference_no'  => 'REF-TEST-002',
            'design_code'   => 'DC-002',
            'target_weight' => 22.000,
            'size'          => '20',
            'quantity'      => 1,
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.work_orders.bulk-export'), [
            'order_ids' => [$order1->id],
        ]);

        $response->assertSuccessful();

        Excel::assertDownloaded(function (\App\Exports\WorkOrdersExport $export) use ($order1, $order2) {
            $collection = $export->collection();
            return $collection->contains('id', $order1->id) && !$collection->contains('id', $order2->id);
        });
    }

    public function test_admin_can_export_filtered_work_orders(): void
    {
        Excel::fake();

        $admin = Admin::factory()->create();

        $order1 = WorkOrder::factory()->create([
            'work_order_no' => 'WO-PENDING-001',
            'status'        => 'pending',
        ]);

        $order2 = WorkOrder::factory()->create([
            'work_order_no' => 'WO-COMPLETED-001',
            'status'        => 'completed',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.work_orders.export', [
            'tab' => 'pending',
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded(function (\App\Exports\WorkOrdersExport $export) use ($order1, $order2) {
            $collection = $export->collection();
            return $collection->contains('id', $order1->id) && !$collection->contains('id', $order2->id);
        });
    }
}
