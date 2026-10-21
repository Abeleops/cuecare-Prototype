<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Loyalty behavior.
 */
class LoyaltyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Posted transaction earns points.
     */
    public function test_posted_transaction_earns_points(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $customer = Customer::create([
            'name' => 'Loyalty Customer',
            'phone' => '09000000000',
            'loyalty_points' => 0,
        ]);
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'source_type' => 'manual',
            'source_id' => 1,
            'subtotal' => 550,
            'tax' => 0,
            'total' => 550,
        ]);
        app(LoyaltyService::class)->earn($customer, $transaction, $user);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'loyalty_points' => 5,
        ]);
        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $customer->id,
            'type' => 'earn',
            'points' => 5,
            'transaction_id' => $transaction->id,
        ]);
    }

    /**
     * Cashier can redeem points but cannot redeem more than balance.
     */
    public function test_cashier_can_redeem_points_but_cannot_redeem_more_than_balance(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $customer = Customer::create([
            'name' => 'Redeem Customer',
            'loyalty_points' => 10,
        ]);
        $this->actingAs($cashier)
            ->post(route('loyalty.redeem'), [
                'customer_id' => $customer->id,
                'points' => 4,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'loyalty_points' => 6,
        ]);
        $this->actingAs($cashier)
            ->post(route('loyalty.redeem'), [
                'customer_id' => $customer->id,
                'points' => 99,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('points');
    }

    /**
     * Staff without loyalty permission gets forbidden.
     */
    public function test_staff_without_loyalty_permission_gets_forbidden(): void
    {
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $this->actingAs($staff)
            ->get(route('loyalty'))
            ->assertForbidden();
    }
}
