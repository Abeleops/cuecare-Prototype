<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Autocare behavior.
 */
class AutocareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cashier can save a complete service and post it to the ledger.
     */
    public function test_cashier_can_save_a_complete_service_and_post_it_to_the_ledger(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $staff = User::factory()
            ->count(3)
            ->create([
                'role' => 'autocare_staff',
            ]);
        CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $service = ServiceCatalog::create([
            'name' => 'Full detailing',
            'price' => 2500,
            'active' => true,
        ]);
        $this->actingAs($user)
            ->post(route('autocare.bookings.store'), [
                'vehicle_type' => 'SUV',
                'plate_number' => 'XYZ-9876',
                'owner_contact' => '09170000000',
                'owner_name' => 'Juan Dela Cruz',
                'model' => 'Montero',
                'service_catalog_id' => $service->id,
                'scheduled_date' => today()->toDateString(),
                'assigned_staff' => $staff->pluck('id')
                    ->all(),
                'payment_method' => 'cash',
                'amount_received' => 3000,
            ])
            ->assertRedirect();
        $booking = ServiceBooking::first();
        $this->assertEquals('SUV', $booking->vehicle->vehicle_type);
        $this->assertDatabaseHas('transactions', [
            'source_type' => ServiceBooking::class,
            'source_id' => $booking->id,
            'total' => 2500,
        ]);
        $this->assertDatabaseHas('payments', [
            'transaction_id' => $booking->transaction->id,
            'method' => 'cash',
            'change_amount' => 500,
        ]);
    }

    /**
     * Required vehicle owner fields are enforced.
     */
    public function test_required_vehicle_owner_fields_are_enforced(): void
    {
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($user)
            ->post(route('autocare.bookings.store'), [])
            ->assertSessionHasErrors([
                'vehicle_type',
                'plate_number',
                'owner_contact',
                'owner_name',
            ]);
    }
}
