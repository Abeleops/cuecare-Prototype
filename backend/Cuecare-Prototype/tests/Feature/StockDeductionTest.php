<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\FoodOrder;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Stock Deduction behavior.
 */
class StockDeductionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirmed sale deducts recipe stock without approval.
     */
    public function test_confirmed_sale_deducts_recipe_stock_without_approval(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $category = MenuCategory::create([
            'name' => 'Meals',
        ]);
        $menu = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Wings',
            'price' => 300,
            'available' => true,
        ]);
        $stock = InventoryItem::create([
            'name' => 'Chicken',
            'quantity' => 10,
            'reorder_level' => 2,
            'unit' => 'kg',
        ]);
        Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $stock->id,
            'quantity' => 0.25,
        ]);
        $this->actingAs($cashier)
            ->post(route('pos.sale'), [
                'items' => [
                    [
                        'menu_item_id' => $menu->id,
                        'description' => 'Wings',
                        'quantity' => 2,
                        'unit_price' => 300,
                    ],
                ],
                'payment_method' => 'card',
            ])
            ->assertRedirect();
        $this->assertEquals(9.5, (float) $stock->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $stock->id,
            'movement_type' => 'sale_deduction',
            'quantity' => -0.5,
            'approval_request_id' => null,
        ]);
        $this->assertSame(1, Sale::count());
    }

    /**
     * Sale rolls back when recipe stock is insufficient.
     */
    public function test_sale_rolls_back_when_recipe_stock_is_insufficient(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $category = MenuCategory::create([
            'name' => 'Meals',
        ]);
        $menu = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Rare dish',
            'price' => 300,
            'available' => true,
        ]);
        $stock = InventoryItem::create([
            'name' => 'Rare stock',
            'quantity' => 0.1,
            'reorder_level' => 1,
            'unit' => 'kg',
        ]);
        Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $stock->id,
            'quantity' => 1,
        ]);
        $this->actingAs($cashier)
            ->post(route('pos.sale'), [
                'items' => [
                    [
                        'menu_item_id' => $menu->id,
                        'description' => 'Rare dish',
                        'quantity' => 1,
                        'unit_price' => 300,
                    ],
                ],
                'payment_method' => 'card',
            ])
            ->assertSessionHasErrors('items');
        $this->assertSame(0, Sale::count());
        $this->assertEquals(0.1, (float) $stock->fresh()->quantity);
        $this->assertSame(0, StockMovement::count());
    }

    /**
     * Confirmed food order also deducts recipe stock.
     */
    public function test_confirmed_food_order_also_deducts_recipe_stock(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $requester = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $category = MenuCategory::create([
            'name' => 'Drinks',
        ]);
        $menu = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Iced Tea',
            'price' => 90,
            'available' => true,
        ]);
        $stock = InventoryItem::create([
            'name' => 'Tea Syrup',
            'quantity' => 2,
            'reorder_level' => 1,
            'unit' => 'bottle',
        ]);
        Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $stock->id,
            'quantity' => 0.1,
        ]);
        $order = FoodOrder::create([
            'requested_by' => $requester->id,
            'table_or_customer' => 'Table 2',
            'status' => 'pending',
            'total' => 180,
        ]);
        $order->items()->create([
            'menu_item_id' => $menu->id,
            'quantity' => 2,
            'unit_price' => 90,
        ]);
        $this->actingAs($cashier)
            ->patch(route('orders.confirm', $order), [
                'payment_method' => 'card',
            ])
            ->assertRedirect();
        $this->assertEquals(1.8, (float) $stock->fresh()->quantity);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $stock->id,
            'movement_type' => 'sale_deduction',
            'quantity' => -0.2,
        ]);
    }
}
