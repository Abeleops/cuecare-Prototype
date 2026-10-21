<?php

namespace Tests\Feature;

use App\Models\BilliardSession;
use App\Models\BilliardTable;
use App\Models\CashShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Billiards behavior.
 */
class BilliardsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Session is prorated and posted to the shared ledger.
     */
    public function test_session_is_prorated_and_posted_to_the_shared_ledger(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $table = BilliardTable::create([
            'name' => 'Table 1',
            'hourly_rate' => 200,
            'price_per_half_hour' => 100,
            'status' => 'available',
        ]);
        $this->actingAs($user)->post(route('billiards.sessions.start', $table));
        $session = BilliardSession::first();
        $session->update([
            'started_at' => now()->subMinutes(30),
        ]);
        $this->post(route('billiards.sessions.end', $session))->assertRedirect();
        $this->assertEquals(100, (float) $session->fresh()->amount);
        $this->assertDatabaseHas('transactions', [
            'source_type' => BilliardSession::class,
            'source_id' => $session->id,
            'total' => 100,
        ]);
        $this->assertEquals('available', $table->fresh()->status);
    }

    /**
     * Cashier cannot manage tables but admin can.
     */
    public function test_cashier_cannot_manage_tables_but_admin_can(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->post(route('billiards.tables.store'), [
                'name' => 'Unauthorized',
                'hourly_rate' => 200,
                'price_per_half_hour' => 100,
            ])
            ->assertForbidden();
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->post(route('billiards.tables.store'), [
                'name' => 'Admin Table',
                'hourly_rate' => 200,
                'price_per_half_hour' => 100,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('billiard_tables', [
            'name' => 'Admin Table',
        ]);
    }
}
