<?php

namespace Tests\Feature;

use App\Models\SecurityAlert;
use App\Models\SecurityRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Cybersecurity behavior.
 */
class CybersecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Security officer can view and manage alerts.
     */
    public function test_security_officer_can_view_and_manage_alerts(): void
    {
        $officer = User::factory()->create([
            'role' => 'security_officer',
        ]);
        $rule = SecurityRule::create([
            'name' => 'Test rule',
            'event_type' => 'failed_login',
            'threshold' => 5,
            'window_minutes' => 10,
            'action' => 'alert',
            'active' => true,
        ]);
        $alert = SecurityAlert::create([
            'security_rule_id' => $rule->id,
            'severity' => 'high',
            'status' => 'open',
            'message' => 'Test alert',
        ]);
        $this->actingAs($officer)
            ->get(route('security'))
            ->assertOk()
            ->assertSee('Test alert');
        $this->actingAs($officer)
            ->patch(route('security.alerts.acknowledge', $alert))
            ->assertRedirect();
        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'status' => 'acknowledged',
        ]);
        $this->actingAs($officer)
            ->patch(route('security.alerts.close', $alert))
            ->assertRedirect();
        $this->assertDatabaseHas('security_alerts', [
            'id' => $alert->id,
            'status' => 'closed',
        ]);
    }

    /**
     * Cashier cannot view or manage security alerts.
     */
    public function test_cashier_cannot_view_or_manage_security_alerts(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get(route('security'))
            ->assertForbidden();
    }
}
