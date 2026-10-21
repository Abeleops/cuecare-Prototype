<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Ledger Reports behavior.
 */
class LedgerReportsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dashboard and reports use shared transactions.
     */
    public function test_dashboard_and_reports_use_shared_transactions(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
        ]);
        $shift = CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 500,
            'opened_at' => now(),
        ]);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'source_type' => 'manual',
            'source_id' => 1,
            'subtotal' => 800,
            'tax' => 0,
            'total' => 800,
            'status' => 'posted',
        ]);
        Payment::create([
            'transaction_id' => $transaction->id,
            'method' => 'cash',
            'amount' => 800,
        ]);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('₱800.00');
        $this->actingAs($user)
            ->post(route('shift.close'), [
                'counted_cash' => 1300,
            ])
            ->assertRedirect();
        $this->assertEquals(1300, (float) $shift->fresh()->expected_cash);
        $this->actingAs($user)
            ->get(route('reports'))
            ->assertOk()
            ->assertSee('₱800.00');
    }
}
