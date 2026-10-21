<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Universal Audit behavior.
 */
class UniversalAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create update and delete events are audited.
     */
    public function test_create_update_and_delete_events_are_audited(): void
    {
        $customer = Customer::create([
            'name' => 'Universal Customer',
            'phone' => '09991112222',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.created',
            'auditable_id' => $customer->id,
        ]);
        $customer->update([
            'name' => 'Updated Customer',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.updated',
            'auditable_id' => $customer->id,
        ]);
        $customer->delete();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.deleted',
            'auditable_id' => $customer->id,
        ]);
    }

    /**
     * Login and logout are audited with actor context.
     */
    public function test_login_and_logout_are_audited_with_actor_context(): void
    {
        $user = User::factory()->create([
            'email' => 'universal@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login.succeeded',
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
        $this->post('/logout')->assertRedirect('/');
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout',
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }
}
