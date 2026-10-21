<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Inventory Permissions behavior.
 */
class InventoryPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cashier can view but cannot mutate inventory.
     */
    public function test_cashier_can_view_but_cannot_mutate_inventory(): void
    {
        $item = InventoryItem::create([
            'name' => 'Coffee beans',
            'quantity' => 10,
            'reorder_level' => 3,
            'unit' => 'kg',
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get(route('inventory'))
            ->assertOk()
            ->assertSee('Coffee beans');
        $this->actingAs($cashier)
            ->post(route('inventory.store'), [
                'name' => 'Sugar',
                'quantity' => 5,
                'reorder_level' => 2,
                'unit' => 'kg',
            ])
            ->assertForbidden();
        $this->actingAs($cashier)
            ->patch(route('inventory.update', $item), [
                'name' => 'Changed',
                'quantity' => 1,
                'reorder_level' => 2,
                'unit' => 'kg',
            ])
            ->assertForbidden();
        $this->actingAs($cashier)
            ->delete(route('inventory.destroy', $item))
            ->assertForbidden();
    }

    /**
     * Admin can add update and delete inventory.
     */
    public function test_admin_can_add_update_and_delete_inventory(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->post(route('inventory.store'), [
                'name' => 'Sugar',
                'quantity' => 5,
                'reorder_level' => 2,
                'unit' => 'kg',
            ])
            ->assertRedirect();
        $item = InventoryItem::where('name', 'Sugar')->firstOrFail();
        $this->actingAs($admin)
            ->patch(route('inventory.update', $item), [
                'name' => 'Brown sugar',
                'quantity' => 4,
                'reorder_level' => 2,
                'unit' => 'kg',
            ])
            ->assertRedirect();
        $this->actingAs($admin)
            ->delete(route('inventory.destroy', $item->fresh()))
            ->assertRedirect();
        $this->assertDatabaseMissing('inventory_items', [
            'name' => 'Brown sugar',
        ]);
    }
}
