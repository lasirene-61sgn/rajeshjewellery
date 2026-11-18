<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Craftsman;
use App\Models\DesignCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CraftsmanImageTest extends TestCase
{
    use DatabaseTransactions;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name'      => 'Test Admin',
            'email'     => 'admin_img_' . uniqid() . '@example.com',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);
    }

    public function test_guests_cannot_access_craftsman_images(): void
    {
        $response = $this->get(route('admin.craftsman_images.index'));
        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_craftsman_images_folders_list(): void
    {
        $craftsman = Craftsman::create([
            'name'           => 'Folder Artisan ' . uniqid(),
            'email'          => 'artisan_' . uniqid() . '@example.com',
            'mobile'         => '91' . rand(10000000, 99999999),
            'password'       => Hash::make('secret123'),
            'plain_password' => 'secret123',
            'is_active'      => true,
        ]);

        $designCode = DesignCode::create([
            'code'     => 'TEST-DC-' . uniqid(),
            'nickname' => 'Artisan Ring',
            'image'    => 'design_codes/test.jpg',
        ]);

        $craftsman->designCodes()->attach($designCode->id);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.craftsman_images.index'));

        $response->assertOk()
            ->assertViewIs('admin.craftsman_images.index')
            ->assertSee($craftsman->name)
            ->assertSee('Craftsman Images');
    }

    public function test_admin_can_search_craftsman_folders(): void
    {
        $craftsman1 = Craftsman::create([
            'name'           => 'Alpha Goldsmith ' . uniqid(),
            'email'          => 'alpha_' . uniqid() . '@example.com',
            'mobile'         => '91' . rand(10000000, 99999999),
            'password'       => Hash::make('secret123'),
            'plain_password' => 'secret123',
            'is_active'      => true,
        ]);

        $craftsman2 = Craftsman::create([
            'name'           => 'Beta Jeweler ' . uniqid(),
            'email'          => 'beta_' . uniqid() . '@example.com',
            'mobile'         => '91' . rand(10000000, 99999999),
            'password'       => Hash::make('secret123'),
            'plain_password' => 'secret123',
            'is_active'      => true,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.craftsman_images.index', ['search' => 'Alpha Goldsmith']));

        $response->assertOk()
            ->assertSee($craftsman1->name)
            ->assertDontSee($craftsman2->name);
    }

    public function test_admin_can_view_specific_craftsman_images_folder(): void
    {
        $craftsman = Craftsman::create([
            'name'           => 'Folder Detail Artisan ' . uniqid(),
            'email'          => 'detail_' . uniqid() . '@example.com',
            'mobile'         => '91' . rand(10000000, 99999999),
            'password'       => Hash::make('secret123'),
            'plain_password' => 'secret123',
            'is_active'      => true,
        ]);

        $codeWithImage = 'CODE-IMG-' . rand(100, 999);
        $design1 = DesignCode::create([
            'code'     => $codeWithImage,
            'nickname' => 'Emerald Pendant',
            'image'    => 'design_codes/emerald.jpg',
        ]);

        $codeWithoutImage = 'CODE-NOIMG-' . rand(100, 999);
        $design2 = DesignCode::create([
            'code'     => $codeWithoutImage,
            'nickname' => 'Gold Bangle',
            'image'    => null,
        ]);

        $craftsman->designCodes()->attach([$design1->id, $design2->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.craftsman_images.show', $craftsman));

        $response->assertOk()
            ->assertViewIs('admin.craftsman_images.show')
            ->assertSee($craftsman->name)
            ->assertSee($codeWithImage)
            ->assertSee('Emerald Pendant')
            ->assertSee($codeWithoutImage)
            ->assertSee('Gold Bangle')
            ->assertSee('No Image');
    }

    public function test_admin_can_filter_craftsman_images_by_status(): void
    {
        $craftsman = Craftsman::create([
            'name'           => 'Filter Artisan ' . uniqid(),
            'email'          => 'filter_' . uniqid() . '@example.com',
            'mobile'         => '91' . rand(10000000, 99999999),
            'password'       => Hash::make('secret123'),
            'plain_password' => 'secret123',
            'is_active'      => true,
        ]);

        $codeWithImg = 'WITH-IMG-' . rand(100, 999);
        $design1 = DesignCode::create([
            'code'     => $codeWithImg,
            'nickname' => 'Has Picture',
            'image'    => 'design_codes/pic.jpg',
        ]);

        $codeWithoutImg = 'NO-IMG-' . rand(100, 999);
        $design2 = DesignCode::create([
            'code'     => $codeWithoutImg,
            'nickname' => 'Missing Picture',
            'image'    => null,
        ]);

        $craftsman->designCodes()->attach([$design1->id, $design2->id]);

        // Filter: with_image
        $responseWith = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'with_image']));

        $responseWith->assertOk()
            ->assertSee($codeWithImg)
            ->assertDontSee($codeWithoutImg);

        // Filter: without_image
        $responseWithout = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.craftsman_images.show', ['craftsman' => $craftsman, 'filter' => 'without_image']));

        $responseWithout->assertOk()
            ->assertSee($codeWithoutImg)
            ->assertDontSee($codeWithImg);
    }
}
