<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module GAccess behavior.
 */
class ModuleGAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sidebar only renders modules allowed for each role.
     */
    public function test_sidebar_only_renders_modules_allowed_for_each_role(): void
    {
        $pages = [
            'admin' => 'dashboard',
            'manager' => 'reports',
            'owner' => 'dashboard',
            'cashier' => 'pos',
            'autocare_staff' => 'orders.request.form',
            'security_officer' => 'security',
        ];
        foreach ($pages as $role => $route) {
            $response = $this->actingAs(User::factory()->create([
                'role' => $role,
            ]))->get(route($route));
            $response->assertOk()->assertSee(str_replace('_', ' ', $role));
            if ($role === 'cashier') {
                $response->assertSee('POS &amp; F&amp;B', false)
                    ->assertSee('Billiards')
                    ->assertSee('Autocare')
                    ->assertSee('Inventory')
                    ->assertDontSee('Overview')
                    ->assertDontSee('Reports')
                    ->assertDontSee('Security');
            }
            if ($role === 'autocare_staff') {
                $response->assertSee('POS &amp; F&amp;B Order request', false)
                    ->assertSee('Inventory')
                    ->assertSee('Autocare')
                    ->assertDontSee('POS &amp; F&amp;B</a>', false)
                    ->assertDontSee('Billiards')
                    ->assertDontSee('Overview');
            }
            if ($role === 'security_officer') {
                $response->assertSee('Security')
                    ->assertDontSee('Overview')
                    ->assertDontSee('POS &amp; F&amp;B', false)
                    ->assertDontSee('Billiards');
            }
            if (in_array($role, [
                'admin',
                'manager',
                'owner',
            ], true)) {
                $response->assertSee('Overview')
                    ->assertSee('POS &amp; F&amp;B', false)
                    ->assertSee('Billiards')
                    ->assertSee('Autocare')
                    ->assertSee('Inventory')
                    ->assertSee('Loyalty')
                    ->assertSee('Reports')
                    ->assertDontSee('POS &amp; F&amp;B Order request', false);
            }
            if ($role === 'manager') {
                $response->assertSee('Audit log')->assertDontSee('Security');
            }
            if ($role === 'owner') {
                $response->assertDontSee('Audit log')->assertDontSee('Security');
            }
            if ($role === 'admin') {
                $response->assertSee('Audit log')->assertSee('Security');
            }
        }
    }

    /**
     * Cashier cannot open overview.
     */
    public function test_cashier_cannot_open_overview(): void
    {
        $this->actingAs(User::factory()
            ->create([
                'role' => 'cashier',
            ]))
            ->get(route('dashboard'))
            ->assertOk();
    }

    /**
     * Autocare staff can request orders but cannot create services or charge.
     */
    public function test_autocare_staff_can_request_orders_but_cannot_create_services_or_charge(): void
    {
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $category = MenuCategory::create([
            'name' => 'Request menu',
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Juice',
            'price' => 80,
            'available' => true,
        ]);
        $this->actingAs($staff)
            ->post(route('orders.request.store'), [
                'table_or_customer' => 'Table 2',
                'items' => [
                    [
                        'menu_item_id' => $item->id,
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('food_orders', [
            'requested_by' => $staff->id,
            'status' => 'pending',
            'table_or_customer' => 'Table 2',
        ]);
        $this->actingAs($staff)
            ->get(route('autocare'))
            ->assertOk();
        $this->actingAs($staff)
            ->post(route('autocare.bookings.store'), [])
            ->assertForbidden();
        $this->actingAs($staff)
            ->post(route('pos.sale'), [])
            ->assertForbidden();
        $this->actingAs($staff)
            ->get(route('inventory'))
            ->assertOk();
        $this->actingAs($staff)
            ->get('/users')
            ->assertForbidden();
        $this->actingAs($staff)
            ->get('/inventory/create')
            ->assertForbidden();
    }

    /**
     * Existing billiards staff users are moved to cashier.
     */
    public function test_existing_billiards_staff_users_are_moved_to_cashier(): void
    {
        $user = User::factory()->create([
            'role' => 'billiards_staff',
        ]);
        $migration = require base_path('database/migrations/2026_10_20_001200_remove_billiards_staff_role.php');
        $migration->up();
        $this->assertSame('cashier', $user->fresh()->role);
        $this->assertDatabaseMissing('roles', [
            'name' => 'billiards_staff',
        ]);
    }
}
