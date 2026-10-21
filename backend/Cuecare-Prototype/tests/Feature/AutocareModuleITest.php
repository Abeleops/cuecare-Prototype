<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\ServiceStatusHistory;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Autocare Module I behavior.
 */
class AutocareModuleITest extends TestCase
{
    use RefreshDatabase;

    /**
     * Service is selected from catalog and status timestamps are system generated.
     */
    public function test_service_is_selected_from_catalog_and_status_timestamps_are_system_generated(): void
    {
        $user = User::factory()->create([
            'role' => 'manager',
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
            'name' => 'Brake service',
            'price' => 3200,
            'active' => true,
        ]);
        $this->actingAs($user)
            ->post(route('autocare.bookings.store'), [
                'vehicle_type' => 'Sedan',
                'plate_number' => 'CAR-100',
                'owner_contact' => '09170000001',
                'owner_name' => 'Ana Cruz',
                'model' => 'Vios',
                'service_catalog_id' => $service->id,
                'scheduled_date' => today()->toDateString(),
                'assigned_staff' => $staff->pluck('id')
                    ->all(),
                'payment_method' => 'card',
            ])
            ->assertRedirect();
        $booking = ServiceBooking::first();
        $this->assertSame('Brake service', $booking->requested_service);
        $this->assertSame(3200.0, (float) $booking->estimated_cost);
        $this->assertNotNull($booking->received_at);
        $this->assertDatabaseHas('service_status_histories', [
            'service_booking_id' => $booking->id,
            'to_status' => 'received',
        ]);
        $this->actingAs($user)
            ->patch(route('autocare.bookings.status', $booking), [
                'status' => 'in progress',
            ])
            ->assertRedirect();
        $booking->refresh();
        $this->assertNotNull($booking->in_progress_at);
        $this->assertSame(2, ServiceStatusHistory::where('service_booking_id', $booking->id)->count());
        $this->actingAs($user)
            ->patch(route('autocare.bookings.status', $booking), [
                'status' => 'released',
            ])
            ->assertStatus(422);
        $this->assertSame('in progress', $booking->fresh()->status);
    }

    /**
     * Status history cannot be updated or deleted.
     */
    public function test_status_history_cannot_be_updated_or_deleted(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '09990000000',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'HIST-1',
            'vehicle_type' => 'sedan',
            'model' => 'One',
        ]);
        $booking = ServiceBooking::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'requested_service' => 'Test',
            'scheduled_date' => today(),
            'status' => 'received',
            'estimated_cost' => 1,
        ]);
        $history = ServiceStatusHistory::create([
            'service_booking_id' => $booking->id,
            'to_status' => 'received',
            'changed_at' => now(),
        ]);
        $this->expectException(\LogicException::class);
        $history->update([
            'to_status' => 'released',
        ]);
    }

    /**
     * Service timeline cannot be edited directly.
     */
    public function test_service_timeline_cannot_be_edited_directly(): void
    {
        $customer = Customer::create([
            'name' => 'Timeline Customer',
            'phone' => '09990000002',
        ]);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate_number' => 'TIME-1',
            'vehicle_type' => 'sedan',
            'model' => 'Two',
        ]);
        $booking = ServiceBooking::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'requested_service' => 'Test',
            'scheduled_date' => today(),
            'status' => 'received',
            'estimated_cost' => 1,
            'received_at' => now(),
        ]);
        $this->expectException(\LogicException::class);
        $booking->update([
            'status' => 'released',
        ]);
    }
}
