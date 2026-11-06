<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\Order;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_common_login_as_admin(): void
    {
        $admin = Admin::create([
            'name'      => 'Super Admin',
            'email'     => 'admin_test_' . uniqid() . '@example.com',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'login'    => $admin->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'role',
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_common_login_as_craftsman_by_mobile(): void
    {
        $mobile = '98' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Master Goldsmith',
            'email'     => 'gold_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('gold123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'login'    => $mobile,
            'password' => 'gold123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.role', 'craftsman')
            ->assertJsonPath('data.user.mobile', $mobile);
    }

    public function test_single_device_login_revokes_previous_token(): void
    {
        $mobile = '91' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Artisan',
            'email'     => 'artisan_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Device 1 logs in
        $res1 = $this->postJson('/api/login', [
            'login'    => $mobile,
            'password' => 'password123',
        ]);
        $res1->assertOk();
        $token1 = $res1->json('data.token');

        // Device 1 can access /api/me
        $this->withToken($token1)->getJson('/api/me')->assertOk();

        // Device 2 logs in with same credentials
        $res2 = $this->postJson('/api/login', [
            'login'    => $mobile,
            'password' => 'password123',
        ]);
        $res2->assertOk();
        $token2 = $res2->json('data.token');

        // Clear in-memory auth cache of test runner
        app('auth')->forgetGuards();

        // Device 1's previous token is now revoked and must be rejected with 401
        $this->withToken($token1)->getJson('/api/me')->assertStatus(401);

        app('auth')->forgetGuards();

        // Device 2's token works
        $this->withToken($token2)->getJson('/api/me')->assertOk();
    }

    public function test_unified_endpoints_for_admin(): void
    {
        $admin = Admin::create([
            'name'      => 'Admin User',
            'email'     => 'admin_u_' . uniqid() . '@example.com',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        // 1. Common Dashboard endpoint
        $dashRes = $this->withToken($token)->getJson('/api/dashboard');
        $dashRes->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonStructure(['data' => ['work_orders', 'customer_orders', 'craftsmen']]);

        // 2. Common Work Orders endpoint
        $woRes = $this->withToken($token)->getJson('/api/work-orders?limit=5');
        $woRes->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('data.pagination.per_page', 5)
            ->assertJsonStructure([
                'data' => [
                    'current_tab',
                    'counts',
                    'tabs',
                    'items',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages', 'next_page_url', 'prev_page_url'],
                ],
            ]);

        // 2a. Admin query with ?tabs=in_process
        $woTabsFilterRes = $this->withToken($token)->getJson('/api/work-orders?tabs=in_process');
        $woTabsFilterRes->assertOk()
            ->assertJsonPath('data.current_tab', 'in_process');

        // 2b. Common Work Orders tabs endpoint
        $tabsRes = $this->withToken($token)->getJson('/api/work-orders/tabs');
        $tabsRes->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonStructure(['data' => ['counts', 'tabs']]);

        // 3. Common Customer Orders endpoint
        $coRes = $this->withToken($token)->getJson('/api/customer-orders?per_page=10');
        $coRes->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('data.pagination.per_page', 10)
            ->assertJsonStructure([
                'data' => [
                    'counts',
                    'items',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages', 'next_page_url', 'prev_page_url'],
                ],
            ]);

        // 4. Craftsmen Directory endpoint (Admin Only)
        $crRes = $this->withToken($token)->getJson('/api/craftsmen?perPage=8');
        $crRes->assertOk()
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('data.pagination.per_page', 8)
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages', 'next_page_url', 'prev_page_url'],
                ],
            ]);
    }

    public function test_unified_endpoints_for_craftsman(): void
    {
        $mobile = '97' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Craftsman Ram',
            'email'     => 'ram_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('ram123'),
            'is_active' => true,
        ]);

        $token = $craftsman->createToken('test')->plainTextToken;

        $uniqueSuffix = substr(uniqid(), -6);
        $workOrder = WorkOrder::create([
            'work_order_no' => 'WO-U-' . $uniqueSuffix,
            'reference_no'  => '1307ESO-' . $uniqueSuffix,
            'craftsman_id'  => $craftsman->id,
            'status'        => 'allocated',
            'product_name'  => 'Gold Bangle',
            'design_code'   => 'GB-101',
            'target_weight' => 12.500,
            'quantity'      => 1,
        ]);

        // 1. Common Dashboard endpoint
        $dashRes = $this->withToken($token)->getJson('/api/dashboard');
        $dashRes->assertOk()
            ->assertJsonPath('role', 'craftsman')
            ->assertJsonStructure(['data' => ['counts', 'recent_orders', 'assigned_designs']]);

        // 2. Common Work Orders endpoint (scoped to this craftsman)
        $woRes = $this->withToken($token)->getJson('/api/work-orders?limit=12');
        $woRes->assertOk()
            ->assertJsonPath('role', 'craftsman')
            ->assertJsonPath('data.counts.allocated', 1)
            ->assertJsonPath('data.pagination.per_page', 12)
            ->assertJsonStructure([
                'data' => [
                    'current_tab',
                    'counts',
                    'tabs',
                    'items',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to', 'has_more_pages', 'next_page_url', 'prev_page_url'],
                ],
            ]);

        // 2b. Common Work Orders tabs endpoint for Craftsman
        $woTabsRes = $this->withToken($token)->getJson('/api/work-orders/tabs');
        $woTabsRes->assertOk()
            ->assertJsonPath('role', 'craftsman')
            ->assertJsonStructure(['data' => ['counts', 'tabs']]);

        // 3. Common Accept action
        $acceptRes = $this->withToken($token)->postJson("/api/work-orders/{$workOrder->id}/accept");
        $acceptRes->assertOk();
        $this->assertEquals('in_process', $workOrder->fresh()->status);

        // 3b. Verify query with ?tabs=in_process filters correctly and returns current_tab
        $inProcessRes = $this->withToken($token)->getJson('/api/work-orders?tabs=in_process');
        $inProcessRes->assertOk()
            ->assertJsonPath('data.current_tab', 'in_process')
            ->assertJsonCount(1, 'data.items');

        // 4. Common Submit action
        $submitRes = $this->withToken($token)->postJson("/api/work-orders/{$workOrder->id}/submit");
        $submitRes->assertOk();
        $this->assertEquals('for_approval', $workOrder->fresh()->status);

        // 5. Craftsmen directory is forbidden for Craftsman!
        $this->withToken($token)->getJson('/api/craftsmen')->assertStatus(403);
    }

    public function test_craftsman_can_reject_work_order_with_reason(): void
    {
        $mobile = '96' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Artisan Suresh',
            'email'     => 'suresh_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('pass123'),
            'is_active' => true,
        ]);

        $token = $craftsman->createToken('test')->plainTextToken;

        $uniqueSuffix = substr(uniqid(), -6);
        $workOrder = WorkOrder::create([
            'work_order_no' => 'WO-REJ-' . $uniqueSuffix,
            'reference_no'  => 'REF-' . $uniqueSuffix,
            'craftsman_id'  => $craftsman->id,
            'status'        => 'allocated',
            'product_name'  => 'Silver Ring',
            'design_code'   => 'SR-101',
            'target_weight' => 5.0,
            'quantity'      => 1,
        ]);

        $rejectRes = $this->withToken($token)->postJson("/api/work-orders/{$workOrder->id}/reject", [
            'reject_reason' => 'Design pattern not feasible with target weight',
        ]);

        $rejectRes->assertOk();
        $this->assertEquals('rejected', $workOrder->fresh()->status);
        $this->assertEquals('Design pattern not feasible with target weight', $workOrder->fresh()->reject_reason);
    }

    public function test_craftsman_can_update_customer_order_status(): void
    {
        $mobile = '95' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Artisan Raju',
            'email'     => 'raju_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('pass123'),
            'is_active' => true,
        ]);

        $token = $craftsman->createToken('test')->plainTextToken;

        $uniqueSuffix = substr(uniqid(), -6);
        $order = Order::create([
            'order_no'       => 'ORD-' . $uniqueSuffix,
            'customer_name'  => 'Customer Test',
            'customer_phone' => '9888877777',
            'product_name'   => 'Necklace',
            'craftsman_id'   => $craftsman->id,
            'status'         => 'allocated',
            'quantity'       => 1,
        ]);

        // Common Customer Orders status update
        $updateRes = $this->withToken($token)->postJson("/api/customer-orders/{$order->id}/status", [
            'status' => 'in_progress',
        ]);

        $updateRes->assertOk();
        $this->assertEquals('in_progress', $order->fresh()->status);
    }

    public function test_craftsman_can_bulk_reject_allocated_work_orders(): void
    {
        $mobile = '94' . rand(10000000, 99999999);
        $craftsman = Craftsman::create([
            'name'      => 'Artisan Bulk',
            'email'     => 'bulk_' . uniqid() . '@example.com',
            'mobile'    => $mobile,
            'password'  => Hash::make('pass123'),
            'is_active' => true,
        ]);

        $token = $craftsman->createToken('test')->plainTextToken;

        $suffix1 = substr(uniqid(), -6);
        $order1 = WorkOrder::create([
            'work_order_no' => 'WO-BLK1-' . $suffix1,
            'reference_no'  => 'REF1-' . $suffix1,
            'craftsman_id'  => $craftsman->id,
            'status'        => 'allocated',
            'product_name'  => 'Ring 1',
            'design_code'   => 'R-1',
            'target_weight' => 4.0,
            'quantity'      => 1,
        ]);

        $suffix2 = substr(uniqid(), -6);
        $order2 = WorkOrder::create([
            'work_order_no' => 'WO-BLK2-' . $suffix2,
            'reference_no'  => 'REF2-' . $suffix2,
            'craftsman_id'  => $craftsman->id,
            'status'        => 'allocated',
            'product_name'  => 'Ring 2',
            'design_code'   => 'R-2',
            'target_weight' => 5.0,
            'quantity'      => 1,
        ]);

        $bulkRes = $this->withToken($token)->postJson('/api/work-orders/bulk-reject', [
            'order_ids'     => [$order1->id, $order2->id],
            'reject_reason' => 'Too high workload for current week',
        ]);

        $bulkRes->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals('rejected', $order1->fresh()->status);
        $this->assertEquals('rejected', $order2->fresh()->status);
        $this->assertEquals('Too high workload for current week', $order1->fresh()->reject_reason);
    }

    public function test_admin_can_bulk_reject_pending_and_allocated_work_orders(): void
    {
        $admin = Admin::create([
            'name'     => 'Admin Test Bulk',
            'email'    => 'admin_bulk_' . uniqid() . '@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $admin->createToken('admin_test')->plainTextToken;

        $suffix1 = substr(uniqid(), -6);
        $order1 = WorkOrder::create([
            'work_order_no' => 'WO-AB1-' . $suffix1,
            'reference_no'  => 'REF1-' . $suffix1,
            'status'        => 'pending',
            'product_name'  => 'Admin Ring 1',
            'design_code'   => 'AR-1',
            'target_weight' => 6.0,
            'quantity'      => 1,
        ]);

        $bulkRes = $this->withToken($token)->postJson('/api/work-orders/bulk-reject', [
            'order_ids'     => [$order1->id],
            'reject_reason' => 'Design cancelled by manager',
        ]);

        $bulkRes->assertOk()->assertJsonPath('success', true);
        $this->assertEquals('rejected', $order1->fresh()->status);
        $this->assertEquals('Design cancelled by manager', $order1->fresh()->reject_reason);
    }
}
