<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Pos Flow behavior.
 */
class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sale payment and shift reconciliation.
     */
    public function test_sale_payment_and_shift_reconciliation(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($user);
        CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 1000,
            'opened_at' => now(),
        ]);
        $this->post('/pos/sales', [
            'items' => [
                [
                    'description' => 'Beef Nachos',
                    'quantity' => 2,
                    'unit_price' => 280,
                    'menu_item_id' => null,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 600,
        ])->assertRedirect();
        $shift = CashShift::first();
        $this->assertEquals(0, (float) $shift->expected_cash);
        $this->assertEquals(1, Sale::count());
        $this->assertEquals(1, Transaction::where('source_type', Sale::class)->count());
        $this->assertDatabaseHas('payments', [
            'transaction_id' => Transaction::first()->id,
            'amount' => 560,
        ]);
        $this->post('/shifts/close', [
            'counted_cash' => 1620,
        ]);
        $this->assertEquals(1560, (float) $shift->fresh()->expected_cash);
        $this->assertEquals(60, (float) $shift->fresh()->variance);
    }

    /**
     * Blank starting cash does not calculate variance.
     */
    public function test_blank_starting_cash_does_not_calculate_variance(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($user)
            ->post('/shifts/open', [
                'starting_cash' => '',
            ])
            ->assertRedirect();
        $shift = CashShift::first();
        $this->assertNull($shift->starting_cash);
        $this->post('/shifts/close', [
            'counted_cash' => 500,
        ])->assertRedirect();
        $this->assertNull($shift->fresh()->expected_cash);
        $this->assertNull($shift->fresh()->variance);
    }

    /**
     * Pos screen displays available food and beverage items.
     */
    public function test_pos_screen_displays_available_food_and_beverage_items(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $category = MenuCategory::create([
            'name' => 'Drinks',
        ]);
        MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Calamansi Juice',
            'price' => 120,
            'available' => true,
        ]);
        $this->actingAs($user)
            ->get('/pos')
            ->assertOk()
            ->assertSee('Calamansi Juice')
            ->assertSee('POS &amp; F&amp;B', false);
    }
}
