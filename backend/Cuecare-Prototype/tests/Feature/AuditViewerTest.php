<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BilliardTable;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Audit Viewer behavior.
 */
class AuditViewerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin can view filtered audit logs.
     */
    public function test_admin_can_view_filtered_audit_logs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user.created',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'new_values' => [
                'email' => $admin->email,
            ],
            'created_at' => now(),
        ]);
        $this->actingAs($admin)
            ->get('/audit?action=user.created&date_from='.now()->toDateString())
            ->assertOk()
            ->assertSee('user.created');
    }

    /**
     * Cashier gets forbidden on audit route and policy.
     */
    public function test_cashier_gets_forbidden_on_audit_route_and_policy(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get('/audit')
            ->assertForbidden();
        $this->assertFalse(app(AuditLogPolicy::class)->viewAny($cashier));
    }

    /**
     * Audit values are readable plain text and keep json storage.
     */
    public function test_audit_values_are_readable_plain_text_and_keep_json_storage(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $table = BilliardTable::create([
            'name' => 'Readable Table',
            'hourly_rate' => 150,
            'price_per_half_hour' => 75,
            'status' => 'available',
        ]);
        $table->update([
            'hourly_rate' => 200,
            'price_per_half_hour' => 100,
        ]);
        $this->assertEquals([
            'hourly_rate' => 150.0,
            'price_per_half_hour' => 75.0,
        ], AuditLog::where('action', 'billiards.rate_changed')->first()->old_values);
        $this->actingAs($admin)
            ->get('/audit?action=billiards.rate_changed')
            ->assertOk()
            ->assertSee('Rate per hour: 150', false)
            ->assertSee('Rate per hour: 150 → 200', false)
            ->assertDontSee('{"hourly_rate"', false);
    }
}
