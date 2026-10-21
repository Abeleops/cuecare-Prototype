<?php

namespace Tests\Feature;

use App\Models\SecurityRule;
use App\Services\SecurityRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Security Rule Engine behavior.
 */
class SecurityRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Threshold creates alert.
     */
    public function test_threshold_creates_alert(): void
    {
        SecurityRule::create([
            'name' => 'Brute force',
            'event_type' => 'failed_login',
            'threshold' => 5,
            'window_minutes' => 10,
            'action' => 'lock_account',
        ]);
        $service = app(SecurityRuleService::class);
        for ($i = 0; $i < 5; $i++) {
            $service->record('failed_login');
        }
        $this->assertDatabaseHas('security_alerts', [
            'severity' => 'high',
            'status' => 'open',
        ]);
    }
}
