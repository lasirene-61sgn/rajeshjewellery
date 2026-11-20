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

    public function test_admin_can_access_craftsman_images_api(): void
    {
        $admin = Admin::create([
            'name'      => 'Admin Images Test',
            'email'     => 'admin_img_api_' . uniqid() . '@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $token = $admin->createToken('admin_test')->plainTextToken;

        $craftsman = Craftsman::create([
            'name'      => 'Artisan Gallery ' . uniqid(),
            'email'     => 'artisan_gallery_' . uniqid() . '@example.com',
            'mobile'    => '91' . rand(10000000, 99999999),
            'password'  => Hash::make('pass123'),
            'is_active' => true,
        ]);

        $design1 = \App\Models\DesignCode::create([
            'code'     => 'API-DC-1-' . rand(100, 999),
            'nickname' => 'Gold Ring',
            'image'    => 'design_codes/test1.jpg',
        ]);

        $design2 = \App\Models\DesignCode::create([
            'code'     => 'API-DC-2-' . rand(100, 999),
            'nickname' => 'Silver Ring',
            'image'    => null,
        ]);

        $craftsman->designCodes()->attach([$design1->id, $design2->id]);

        // 1. Index endpoint
        $resIndex = $this->withToken($token)->getJson('/api/craftsman-images?perPage=5');
        $resIndex->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'stats' => ['total_craftsmen', 'total_design_codes', 'total_images'],
                    'items',
                    'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);

        // 2. Show endpoint
        $resShow = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?perPage=10");
        $resShow->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('data.craftsman.id', $craftsman->id)
            ->assertJsonPath('data.stats.total', 2)
            ->assertJsonPath('data.stats.with_images', 1)
            ->assertJsonPath('data.stats.without_images', 1)
            ->assertJsonStructure([
                'data' => [
                    'craftsman' => ['id', 'name', 'email', 'mobile', 'is_active'],
                    'stats'     => ['total', 'with_images', 'without_images'],
                    'items',
                    'pagination',
                ],
            ]);

        // 3. Filter with_image
        $resFilterWith = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?filter=with_image");
        $resFilterWith->assertOk();
        $this->assertCount(1, $resFilterWith->json('data.items'));
        $this->assertEquals($design1->code, $resFilterWith->json('data.items.0.code'));

        // 4. Filter without_image
        $resFilterWithout = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?filter=without_image");
        $resFilterWithout->assertOk();
        $this->assertCount(1, $resFilterWithout->json('data.items'));
        $this->assertEquals($design2->code, $resFilterWithout->json('data.items.0.code'));

        // 5. Test product_name filter and response attributes
        WorkOrder::create([
            'work_order_no' => 'WO-IMG-TEST-' . rand(100, 999),
            'reference_no'  => 'REF-IMG-1',
            'product_name'  => 'Special Choker',
            'design_code'   => $design1->code,
            'craftsman_id'  => $craftsman->id,
            'quantity'      => 1,
            'target_weight' => 12.5,
        ]);

        $resFilterProduct = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?product_name=Special+Choker");
        $resFilterProduct->assertOk();
        $this->assertCount(1, $resFilterProduct->json('data.items'));
        $this->assertEquals($design1->code, $resFilterProduct->json('data.items.0.code'));
        $this->assertEquals('Special Choker', $resFilterProduct->json('data.items.0.product_name'));
        $this->assertContains('Special Choker', $resFilterProduct->json('data.product_names'));

        // 6. Test filters=product_names returns only product names list
        $resOnlyProducts = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?filters=product_names");
        $resOnlyProducts->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'product_names');
        $this->assertContains('Special Choker', $resOnlyProducts->json('product_names'));
        $this->assertNull($resOnlyProducts->json('data.items'));

        // 7. Test dedicated craftsman product-names endpoint
        $resDedicated = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}/product-names");
        $resDedicated->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'product_names');
        $this->assertContains('Special Choker', $resDedicated->json('product_names'));

        // 8. Test nickname filter
        $resFilterNick = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?nickname=Gold+Ring");
        $resFilterNick->assertOk();
        $this->assertCount(1, $resFilterNick->json('data.items'));
        $this->assertEquals($design1->code, $resFilterNick->json('data.items.0.code'));
        $this->assertEquals('Gold Ring', $resFilterNick->json('data.items.0.nickname'));
        $this->assertContains('Gold Ring', $resFilterNick->json('data.nicknames'));

        // 9. Test filters=nicknames returns only nicknames list
        $resOnlyNicks = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?filters=nicknames");
        $resOnlyNicks->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'nicknames');
        $this->assertContains('Gold Ring', $resOnlyNicks->json('nicknames'));
        $this->assertContains('Silver Ring', $resOnlyNicks->json('nicknames'));
        $this->assertNull($resOnlyNicks->json('data.items'));

        // 10. Test dedicated craftsman nicknames endpoint
        $resDedicatedNicks = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}/nicknames");
        $resDedicatedNicks->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'nicknames');
        $this->assertContains('Gold Ring', $resDedicatedNicks->json('nicknames'));
        $this->assertContains('Silver Ring', $resDedicatedNicks->json('nicknames'));

        // 11. Test filters=all (or ?filters=both) returns both product names and design nicknames
        $resBothFilters = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?filters=all");
        $resBothFilters->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'all');
        $this->assertContains('Special Choker', $resBothFilters->json('filters.product_names'));
        $this->assertContains('Gold Ring', $resBothFilters->json('filters.design_nicknames'));

        // 12. Test dedicated /filters endpoint returns both filters
        $resDedicatedBoth = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}/filters");
        $resDedicatedBoth->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('filter', 'all');
        $this->assertContains('Special Choker', $resDedicatedBoth->json('product_names'));
        $this->assertContains('Gold Ring', $resDedicatedBoth->json('design_nicknames'));

        // 13. Test filtering by both product_name and design_nickname at the same time
        $resBothQuery = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}?product_name=Special+Choker&design_nickname=Gold+Ring");
        $resBothQuery->assertOk();
        $this->assertCount(1, $resBothQuery->json('data.items'));
        $this->assertEquals($design1->code, $resBothQuery->json('data.items.0.code'));
        $this->assertEquals('Special Choker', $resBothQuery->json('data.items.0.product_name'));
        $this->assertEquals('Gold Ring', $resBothQuery->json('data.items.0.design_nickname'));
    }

    public function test_craftsman_is_forbidden_from_craftsman_images_api(): void
    {
        $craftsman = Craftsman::create([
            'name'      => 'Artisan Blocked',
            'email'     => 'blocked_' . uniqid() . '@example.com',
            'mobile'    => '92' . rand(10000000, 99999999),
            'password'  => Hash::make('pass123'),
            'is_active' => true,
        ]);

        $token = $craftsman->createToken('craftsman_test')->plainTextToken;

        // Craftsman cannot access index
        $resIndex = $this->withToken($token)->getJson('/api/craftsman-images');
        $resIndex->assertStatus(403)
            ->assertJsonPath('success', false);

        // Craftsman cannot access show
        $resShow = $this->withToken($token)->getJson("/api/craftsman-images/{$craftsman->id}");
        $resShow->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_work_order_api_pagination_and_stamp_attributes(): void
    {
        $admin = Admin::create([
            'name'      => 'Admin WO Test',
            'email'     => 'admin_wo_test_' . uniqid() . '@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $token = $admin->createToken('admin_wo')->plainTextToken;

        // Create 2 test work orders: one with ESO seal and returned status
        $wo1 = WorkOrder::create([
            'work_order_no' => 'WO-TEST-PAG-1',
            'reference_no'  => 'ESO-999-A',
            'product_name'  => 'Gold Chain ESO',
            'design_code'   => 'DC-ESO-01',
            'quantity'      => 5,
            'target_weight' => 2.500,
            'status'        => 'returned',
            'return_count'  => 1,
            'return_reason' => 'Fix Polish',
            'return_due_date' => now()->addDays(2)->toDateString(),
        ]);

        $wo2 = WorkOrder::create([
            'work_order_no' => 'WO-TEST-PAG-2',
            'reference_no'  => 'REG-888-B',
            'product_name'  => 'Silver Ring',
            'design_code'   => 'DC-REG-02',
            'quantity'      => 3,
            'target_weight' => 1.200,
            'status'        => 'allocated',
        ]);

        // 1. Test pagination with limit and page
        $res = $this->withToken($token)->getJson('/api/work-orders?limit=1&page=1');
        $res->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('current_page', 1)
            ->assertJsonStructure([
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                'meta'       => ['current_page', 'last_page', 'per_page', 'total'],
                'data'       => [
                    'items',
                    'pagination',
                    'meta',
                ],
            ]);

        // 2. Test pagination with current_page and per_page query params
        $res2 = $this->withToken($token)->getJson('/api/work-orders?per_page=1&current_page=2');
        $res2->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('current_page', 2);

        // 3. Test transformWorkOrder has seal and reorder badges
        $resSingle = $this->withToken($token)->getJson("/api/work-orders/{$wo1->id}");
        $resSingle->assertOk()
            ->assertJsonPath('data.has_seal', true)
            ->assertJsonPath('data.need_916_seal', true)
            ->assertJsonPath('data.seal_text', 'NEED 916 SEAL ONLY')
            ->assertJsonPath('data.is_reorder', true)
            ->assertJsonPath('data.reorder', true)
            ->assertJsonPath('data.reorder_text', 'REORDER');

        // 4. Test order with design_image returns proper storage/ URL (not api/work-orders/... path)
        $wo1->update(['design_image' => 'design_codes/test_badge.jpg']);
        $resWithImg = $this->withToken($token)->getJson("/api/work-orders/{$wo1->id}");
        $resWithImg->assertOk();
        $designUrl = (string) $resWithImg->json('data.design_image_url');
        $this->assertStringContainsString('/storage/', $designUrl);
        $this->assertStringNotContainsString('/api/work-orders/', $designUrl);
    }

    public function test_work_orders_order_types_includes_eso_and_filters_reference_eso(): void
    {
        $admin = Admin::create([
            'name'      => 'Admin ESO Test',
            'email'     => 'admin_eso_' . uniqid() . '@example.com',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);
        $token = $admin->createToken('admin-eso-test')->plainTextToken;

        // 1. Check dedicated order-types endpoint includes ESO
        $resTypes = $this->withToken($token)->getJson('/api/work-orders/order-types');
        $resTypes->assertOk()
            ->assertJsonPath('success', true);
        $orderTypes = $resTypes->json('order_types') ?? $resTypes->json('data') ?? [];
        $this->assertTrue(collect($orderTypes)->contains(fn ($val) => strcasecmp($val, 'ESO') === 0));

        // 2. Create sample work orders: one with ESO in reference_no, one regular
        $uniqueEsoRef = 'ESO-UNIT-' . uniqid();
        $uniqueRegRef = 'REG-UNIT-' . uniqid();

        $woEso = WorkOrder::create([
            'work_order_no'  => 'WO-ESO-' . rand(1000, 9999),
            'reference_no'   => $uniqueEsoRef,
            'product_name'   => 'Gold ESO Pendant',
            'design_code'    => 'DC-ESO-TEST',
            'quantity'       => 1,
            'target_weight'  => 3.200,
            'unit_type'      => 'pcs',
            'status'         => 'pending',
            'order_type'     => null,
        ]);

        $woReg = WorkOrder::create([
            'work_order_no'  => 'WO-REG-' . rand(1000, 9999),
            'reference_no'   => $uniqueRegRef,
            'product_name'   => 'Regular Ring',
            'design_code'    => 'DC-REG-TEST',
            'quantity'       => 1,
            'target_weight'  => 1.500,
            'unit_type'      => 'pcs',
            'status'         => 'pending',
            'order_type'     => 'Regular',
        ]);

        // 3. Filter by order_type=ESO in API
        $resFilter = $this->withToken($token)->getJson('/api/work-orders?order_type=ESO&per_page=100');
        $resFilter->assertOk();
        $items = collect($resFilter->json('data.items') ?? $resFilter->json('data') ?? []);
        $matchedEso = $items->firstWhere('reference_no', $uniqueEsoRef);
        $matchedReg = $items->firstWhere('reference_no', $uniqueRegRef);

        $this->assertNotNull($matchedEso, 'Work order with ESO in reference_no must be included when filtering by order_type=ESO');
        $this->assertNull($matchedReg, 'Regular work order without ESO must not be included when filtering by order_type=ESO');

        // 4. Test unauthenticated image route is publicly accessible (does not return 401)
        $publicImgRes = $this->get('/api/work-orders/351/image');
        $this->assertNotEquals(401, $publicImgRes->getStatusCode(), 'Image route must be publicly accessible without requiring sanctum auth token');

        // 5. Test admin web view with order_type=ESO
        $webRes = $this->actingAs($admin, 'admin')->get(route('admin.work_orders.index', ['order_type' => 'ESO']));
        $webRes->assertOk();
    }
}
