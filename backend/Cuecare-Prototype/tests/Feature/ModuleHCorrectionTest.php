<?php

namespace Tests\Feature;

use App\Models\BilliardSession;
use App\Models\BilliardTable;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module HCorrection behavior.
 */
class ModuleHCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pos charge requires an open shift.
     */
    public function test_pos_charge_requires_an_open_shift(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($user)
            ->post(route('pos.sale'), [
                'items' => [
                    [
                        'description' => 'Snack',
                        'quantity' => 1,
                        'unit_price' => 50,
                    ],
                ],
                'payment_method' => 'card',
            ])
            ->assertSessionHasErrors('shift');
        $this->assertSame(0, Sale::count());
    }

    /**
     * Billiards charge requires an open shift.
     */
    public function test_billiards_charge_requires_an_open_shift(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $table = BilliardTable::create([
            'name' => 'Shift Table',
            'hourly_rate' => 200,
            'price_per_half_hour' => 100,
            'status' => 'available',
        ]);
        $this->actingAs($user)->post(route('billiards.sessions.start', $table));
        $session = BilliardSession::first();
        $this->actingAs($user)
            ->post(route('billiards.sessions.end', $session))
            ->assertSessionHasErrors('shift');
        $this->assertSame(0, $session->fresh()->amount);
    }

    /**
     * Logout auto closes open shift without counted cash.
     */
    public function test_logout_auto_closes_open_shift_without_counted_cash(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $shift = CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 500,
            'opened_at' => now(),
        ]);
        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');
        $shift = $shift->fresh();
        $this->assertSame('closed', $shift->status);
        $this->assertSame('auto-closed on logout', $shift->close_reason);
        $this->assertNotNull($shift->closed_at);
        $this->assertNull($shift->counted_cash);
    }
}
