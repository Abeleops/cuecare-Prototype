<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Login behavior.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Demo user can log in and receive a session.
     */
    public function test_demo_user_can_log_in_and_receive_a_session(): void
    {
        User::factory()->create([
            'email' => 'admin@cuecare.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $response = $this->post('/login', [
            'email' => 'admin@cuecare.test',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
