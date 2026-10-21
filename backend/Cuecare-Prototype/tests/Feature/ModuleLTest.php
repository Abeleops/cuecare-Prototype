<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BilliardTable;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module L behavior.
 */
class ModuleLTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Billiard table delete is audited.
     */
    public function test_billiard_table_delete_is_audited(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $table = BilliardTable::create([
            'name' => 'Delete Me',
            'hourly_rate' => 200,
            'price_per_half_hour' => 100,
            'status' => 'available',
        ]);
        $this->actingAs($admin)
            ->delete(route('billiards.tables.destroy', $table))
            ->assertRedirect();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'billiards.table_deleted',
            'auditable_id' => $table->id,
            'user_id' => $admin->id,
        ]);
        $log = AuditLog::where('action', 'billiards.table_deleted')->first();
        $this->assertSame('Delete Me', $log->new_values['name']);
        $this->assertEquals(200, (float) $log->new_values['hourly_rate']);
    }

    /**
     * Inventory preset unit saves and other requires text.
     */
    public function test_inventory_preset_unit_saves_and_other_requires_text(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->post(route('inventory.store'), [
                'name' => 'Preset',
                'quantity' => 2,
                'reorder_level' => 1,
                'unit' => 'pcs',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Preset',
            'unit' => 'pcs',
        ]);
        $this->actingAs($admin)
            ->post(route('inventory.store'), [
                'name' => 'Missing custom',
                'quantity' => 2,
                'reorder_level' => 1,
                'unit' => 'Other',
            ])
            ->assertSessionHasErrors('unit_other');
    }

    /**
     * New rows have non null timestamps.
     */
    public function test_new_rows_have_non_null_timestamps(): void
    {
        $customer = Customer::create([
            'name' => 'Timestamp Customer',
        ]);
        $this->assertNotNull($customer->created_at);
        $this->assertNotNull($customer->updated_at);
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
            'created_at' => null,
        ]);
    }

    /**
     * Audit view uses readable ampm timestamp.
     */
    public function test_audit_view_uses_readable_ampm_timestamp(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'timeline.test',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
            'new_values' => [
                'message' => 'time',
            ],
            'created_at' => now(),
        ]);
        $response = $this->actingAs($admin)->get(route('audit'));
        $response->assertOk();
        $this->assertMatchesRegularExpression('/(AM|PM)/', $response->getContent());
        $this->assertStringNotContainsString(now()->utc()->format('Y-m-d\TH:i:s'), $response->getContent());
    }
}
