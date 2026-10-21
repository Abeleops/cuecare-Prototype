<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verify Module M behavior.
 */
class ModuleMTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cashier cannot use menu management routes.
     */
    public function test_cashier_cannot_use_menu_management_routes(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $category = MenuCategory::create([
            'name' => 'Drinks',
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Tea',
            'price' => 50,
            'available' => true,
        ]);
        $this->actingAs($cashier)
            ->get(route('menu'))
            ->assertForbidden();
        $this->actingAs($cashier)
            ->post(route('menu.store'), [
                'menu_category_id' => $category->id,
                'name' => 'Coffee',
                'price' => 60,
            ])
            ->assertForbidden();
        $this->actingAs($cashier)
            ->patch(route('menu.update', $item), [
                'menu_category_id' => $category->id,
                'name' => 'Tea',
                'price' => 55,
            ])
            ->assertForbidden();
        $this->actingAs($cashier)
            ->delete(route('menu.destroy', $item))
            ->assertForbidden();
    }

    /**
     * Manager can add edit delete menu and audit changes.
     */
    public function test_manager_can_add_edit_delete_menu_and_audit_changes(): void
    {
        Storage::fake('public');
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $category = MenuCategory::create([
            'name' => 'Meals',
        ]);
        $this->actingAs($manager)
            ->post(route('menu.store'), [
                'menu_category_id' => $category->id,
                'name' => 'Burger',
                'price' => 120,
                'available' => 1,
                'picture' => UploadedFile::fake()
                    ->image('burger.jpg'),
            ])
            ->assertRedirect();
        $item = MenuItem::first();
        $this->assertNotNull($item->picture_path);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'menu.created',
            'auditable_id' => $item->id,
            'user_id' => $manager->id,
        ]);
        $this->actingAs($manager)
            ->patch(route('menu.update', $item), [
                'menu_category_id' => $category->id,
                'name' => 'Burger Deluxe',
                'price' => 150,
                'available' => 1,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'menu.price_changed',
            'auditable_id' => $item->id,
            'user_id' => $manager->id,
        ]);
        $this->actingAs($manager)
            ->delete(route('menu.destroy', $item))
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'menu.deleted',
            'auditable_id' => $item->id,
            'user_id' => $manager->id,
        ]);
    }

    /**
     * Reports show the account recorded on each transaction.
     */
    public function test_reports_show_the_account_recorded_on_each_transaction(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $shift = CashShift::create([
            'user_id' => $manager->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        Transaction::create([
            'user_id' => $manager->id,
            'shift_id' => $shift->id,
            'source_type' => 'manual',
            'source_id' => 1,
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'status' => 'posted',
        ]);
        $this->actingAs($manager)
            ->get(route('reports'))
            ->assertOk()
            ->assertSee($manager->name)
            ->assertSee('₱100.00');
    }
}
