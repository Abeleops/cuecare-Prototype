<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\ServiceBooking;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module N behavior.
 */
class ModuleNTest extends TestCase
{
    use RefreshDatabase;

    private function staff()
    {
        return User::factory()
            ->count(3)
            ->create([
                'role' => 'autocare_staff',
            ]);
    }

    /**
     * Cashier and autocare staff cannot change status.
     */
    public function test_cashier_and_autocare_staff_cannot_change_status(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $booking = ServiceBooking::create([
            'vehicle_id' => Vehicle::create([
                'plate_number' => 'STATUS-1',
                'vehicle_type' => 'SUV',
                'model' => 'X',
            ])->id,
            'requested_service' => 'Test',
            'scheduled_date' => today(),
            'status' => 'received',
            'estimated_cost' => 1,
        ]);
        $this->actingAs($cashier)
            ->patch(route('autocare.bookings.status', $booking), [
                'status' => 'in progress',
            ])
            ->assertForbidden();
        $this->actingAs($staff)
            ->patch(route('autocare.bookings.status', $booking), [
                'status' => 'in progress',
            ])
            ->assertForbidden();
        $this->actingAs($manager)
            ->patch(route('autocare.bookings.status', $booking), [
                'status' => 'in progress',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'autocare.status_changed',
            'auditable_id' => $booking->id,
            'user_id' => $manager->id,
        ]);
    }

    /**
     * Service requires exactly three active staff and assigns them.
     */
    public function test_service_requires_exactly_three_active_staff_and_assigns_them(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        CashShift::create([
            'user_id' => $manager->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $catalog = ServiceCatalog::create([
            'name' => 'Wash',
            'price' => 500,
            'active' => true,
        ]);
        $staff = $this->staff();
        $base = [
            'vehicle_type' => 'SUV',
            'plate_number' => 'N-100',
            'owner_contact' => '09170000001',
            'owner_name' => 'Owner',
            'model' => 'X',
            'service_catalog_id' => $catalog->id,
            'scheduled_date' => today()->toDateString(),
            'payment_method' => 'card',
        ];
        $this->actingAs($manager)
            ->post(route('autocare.bookings.store'), $base)
            ->assertSessionHasErrors('assigned_staff');
        $this->actingAs($manager)
            ->post(route('autocare.bookings.store'), $base + [
                'assigned_staff' => $staff->pluck('id')
                    ->all(),
            ])
            ->assertRedirect();
        $this->assertCount(3, ServiceBooking::first()->assignedStaff);
    }

    /**
     * Admin can manage staff and service deletion is protected and audited.
     */
    public function test_admin_can_manage_staff_and_service_deletion_is_protected_and_audited(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->post(route('autocare.staff.store'), [
                'name' => 'New Staff',
                'email' => 'newstaff@example.test',
                'password' => 'password123',
            ])
            ->assertRedirect();
        $new = User::where('email', 'newstaff@example.test')->first();
        $this->assertSame('autocare_staff', $new->role);
        $this->actingAs($admin)
            ->delete(route('autocare.staff.destroy', $new))
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deleted',
            'auditable_id' => $new->id,
        ]);
        $booking = ServiceBooking::create([
            'vehicle_id' => Vehicle::create([
                'plate_number' => 'DELETE-1',
                'vehicle_type' => 'SUV',
                'model' => 'X',
            ])->id,
            'requested_service' => 'Delete service',
            'scheduled_date' => today(),
            'status' => 'received',
            'estimated_cost' => 1,
        ]);
        $this->actingAs($admin)
            ->delete(route('autocare.bookings.destroy', $booking))
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'service_booking.deleted',
            'auditable_id' => $booking->id,
            'user_id' => $admin->id,
        ]);
    }
}
