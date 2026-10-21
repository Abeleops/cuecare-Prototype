<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module GCorrection behavior.
 */
class ModuleGCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cashier autocare staff and admin login to dashboard without forbidden.
     */
    public function test_cashier_autocare_staff_and_admin_login_to_dashboard_without_forbidden(): void
    {
        foreach ([
            'cashier',
            'autocare_staff',
            'admin',
        ] as $role) {
            $user = User::factory()->create([
                'email' => $role.'@example.test',
                'password' => 'password',
                'role' => $role,
            ]);
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);
            $response->assertRedirect(route('dashboard'));
            $response = $this->actingAs($user)->get(route('dashboard'));
            $response->assertOk()->assertDontSee('403');
            $this->post('/logout');
        }
    }

    /**
     * Dashboard cards are filtered by role.
     */
    public function test_dashboard_cards_are_filtered_by_role(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Today sales')
            ->assertSee('Active tables')
            ->assertSee('Service queue')
            ->assertSee('Low stock items')
            ->assertDontSee('Open alerts');
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Service queue')
            ->assertSee('Low stock items')
            ->assertSee('Request F&amp;B order', false)
            ->assertDontSee('Today sales')
            ->assertDontSee('Active tables');
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Today sales')
            ->assertSee('Active tables')
            ->assertSee('Service queue')
            ->assertSee('Low stock items')
            ->assertSee('Open alerts');
    }

    /**
     * Only autocare staff allowed routes are available.
     */
    public function test_only_autocare_staff_allowed_routes_are_available(): void
    {
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $this->actingAs($staff)
            ->get('/autocare')
            ->assertOk();
        $this->actingAs($staff)
            ->get('/inventory')
            ->assertOk();
        $this->actingAs($staff)
            ->get('/pos/order-requests/create')
            ->assertOk();
        foreach ([
            '/pos',
            '/users',
            '/audit',
            '/inventory/create',
        ] as $url) {
            $this->actingAs($staff)
                ->get($url)
                ->assertForbidden();
        }
    }
}
