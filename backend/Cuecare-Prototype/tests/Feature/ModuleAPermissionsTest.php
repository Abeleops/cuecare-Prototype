<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module APermissions behavior.
 */
class ModuleAPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cashier cannot open management reports.
     */
    public function test_cashier_cannot_open_management_reports(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get(route('reports'))
            ->assertForbidden();
    }

    /**
     * Manager can open management reports.
     */
    public function test_manager_can_open_management_reports(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $this->actingAs($manager)
            ->get(route('reports'))
            ->assertOk();
    }
}
