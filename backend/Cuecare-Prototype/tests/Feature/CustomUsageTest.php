<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Custom Usage behavior.
 */
class CustomUsageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin can customize usage and custom amount is deducted.
     */
    public function test_admin_can_customize_usage_and_custom_amount_is_deducted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $category = MenuCategory::create([
            'name' => 'Custom menu',
        ]);
        $menu = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Custom drink',
            'price' => 100,
            'available' => true,
        ]);
        $stock = InventoryItem::create([
            'name' => 'Syrup',
            'quantity' => 10,
            'reorder_level' => 1,
            'unit' => 'shot',
        ]);
        $recipe = Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $stock->id,
            'quantity' => 1,
        ]);
        $this->actingAs($admin)
            ->get(route('pos'))
            ->assertOk()
            ->assertSee('Customize usage');
        $this->actingAs($admin)
            ->post(route('pos.customize-usage'), [
                'menu_item_id' => $menu->id,
                'inventory_item_id' => $stock->id,
                'quantity' => 2,
            ])
            ->assertRedirect();
        CashShift::create([
            'user_id' => $admin->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $this->actingAs($admin)
            ->post(route('pos.sale'), [
                'items' => [
                    [
                        'menu_item_id' => $menu->id,
                        'description' => $menu->name,
                        'quantity' => 1,
                        'unit_price' => 100,
                    ],
                ],
                'payment_method' => 'card',
            ])
            ->assertRedirect();
        $this->assertEquals(8, (float) $stock->fresh()->quantity);
        $this->assertEquals([
            ''.$stock->id => 2.0,
        ], SaleItem::first()->custom_recipe_usage);
    }

    /**
     * Cashier cannot customize usage even directly.
     */
    public function test_cashier_cannot_customize_usage_even_directly(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->post(route('pos.customize-usage'), [
                'menu_item_id' => 1,
                'inventory_item_id' => 1,
                'quantity' => 2,
            ])
            ->assertForbidden();
        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertOk()
            ->assertDontSee('Customize usage');
    }
}
