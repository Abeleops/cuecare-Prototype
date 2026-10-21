<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module O behavior.
 */
class ModuleOTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Account history contains only selected account transactions.
     */
    public function test_account_history_contains_only_selected_account_transactions(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $other = User::factory()->create([
            'role' => 'cashier',
        ]);
        Transaction::create([
            'user_id' => $manager->id,
            'source_type' => 'pos',
            'source_id' => 1,
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'status' => 'posted',
        ]);
        Transaction::create([
            'user_id' => $other->id,
            'source_type' => 'pos',
            'source_id' => 2,
            'subtotal' => 200,
            'tax' => 0,
            'total' => 200,
            'status' => 'posted',
        ]);
        $this->actingAs($manager)
            ->get(route('reports.accounts.transactions', $manager))
            ->assertOk()
            ->assertSee('₱100.00')
            ->assertDontSee('₱200.00');
    }

    /**
     * Cashier cannot open account history.
     */
    public function test_cashier_cannot_open_account_history(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get(route('reports.accounts.transactions', $manager))
            ->assertForbidden();
    }

    /**
     * Reports include transactions under each shift.
     */
    public function test_reports_include_transactions_under_each_shift(): void
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
            'source_type' => 'billiards',
            'source_id' => 9,
            'subtotal' => 300,
            'tax' => 0,
            'total' => 300,
            'status' => 'posted',
        ]);
        $this->actingAs($manager)
            ->get(route('reports'))
            ->assertOk()
            ->assertSee('Billiard')
            ->assertSee('₱300.00');
    }
}
