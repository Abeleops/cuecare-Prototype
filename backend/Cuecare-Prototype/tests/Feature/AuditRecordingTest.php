<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BilliardTable;
use App\Models\InventoryItem;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Verify Audit Recording behavior.
 */
class AuditRecordingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User creation and locking are audited.
     */
    public function test_user_creation_and_locking_are_audited(): void
    {
        $user = User::factory()->create();
        $this->assertSame(1, AuditLog::where('action', 'user.created')->count());
        $user->update([
            'locked_at' => now(),
        ]);
        $this->assertSame(1, AuditLog::where('action', 'user.locked')->count());
        $log = AuditLog::where('action', 'user.locked')->first();
        $this->assertNull($log->old_values['locked_at']);
        $this->assertNotNull($log->new_values['locked_at']);
    }

    /**
     * Role and permission changes are audited.
     */
    public function test_role_and_permission_changes_are_audited(): void
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'audited-role',
            'guard_name' => 'web',
        ]);
        $permission = Permission::create([
            'name' => 'audited.permission',
            'guard_name' => 'web',
        ]);
        $before = AuditLog::whereIn('action', [
            'role.attached',
            'permission.attached',
        ])->count();
        $user->assignRole($role);
        $role->givePermissionTo($permission);
        $this->assertSame($before + 2, AuditLog::whereIn('action', [
            'role.attached',
            'permission.attached',
        ])->count());
    }

    /**
     * Table rate and menu price changes are audited.
     */
    public function test_table_rate_and_menu_price_changes_are_audited(): void
    {
        $table = BilliardTable::create([
            'name' => 'Audit Table',
            'hourly_rate' => 200,
            'price_per_half_hour' => 100,
            'status' => 'available',
        ]);
        $table->update([
            'hourly_rate' => 250,
            'price_per_half_hour' => 125,
        ]);
        $this->assertSame(1, AuditLog::where('action', 'billiards.rate_changed')->count());
        $rateLog = AuditLog::where('action', 'billiards.rate_changed')->first();
        $this->assertEquals(200, (float) $rateLog->old_values['hourly_rate']);
        $this->assertEquals(250, (float) $rateLog->new_values['hourly_rate']);
        $category = MenuCategory::create([
            'name' => 'Audit category',
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Audit item',
            'price' => 50,
            'available' => true,
        ]);
        $item->update([
            'price' => 60,
        ]);
        $this->assertSame(1, AuditLog::where('action', 'menu.price_changed')->count());
    }

    /**
     * Inventory add and delete are audited.
     */
    public function test_inventory_add_and_delete_are_audited(): void
    {
        $item = InventoryItem::create([
            'name' => 'Audit stock',
            'quantity' => 5,
            'reorder_level' => 2,
            'unit' => 'pcs',
        ]);
        $this->assertSame(1, AuditLog::where('action', 'inventory.created')->count());
        $item->delete();
        $this->assertSame(1, AuditLog::where('action', 'inventory.deleted')->count());
    }
}
