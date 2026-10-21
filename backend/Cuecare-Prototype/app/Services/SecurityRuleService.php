<?php

namespace App\Services;

use App\Models\SecurityAlert;
use App\Models\SecurityEvent;
use App\Models\SecurityRule;

/** Records security events and evaluates active correlation rules. */
class SecurityRuleService
{
    /** Records an event with the supplied account and metadata, returning the event. */
    public function record(string $type, ?int $userId = null, array $meta = []): SecurityEvent
    {
        $event = SecurityEvent::create([
            'type' => $type,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'metadata' => $meta,
        ]);

        foreach (SecurityRule::where('active', true)
            ->where('event_type', $type)
            ->get() as $rule) {
            $count = SecurityEvent::where('type', $type)
                ->where('created_at', '>=', now()->subMinutes($rule->window_minutes))
                ->count();

            if ($count >= $rule->threshold) {
                SecurityAlert::create([
                    'security_rule_id' => $rule->id,
                    'severity' => 'high',
                    'status' => 'open',
                    'message' => "{$rule->name}: {$count} {$type} events detected.",
                ]);
            }
        }

        return $event;
    }

    /** Updates the supplied alert's status timestamp and returns the refreshed alert. */
    public function changeAlertStatus(SecurityAlert $alert, string $status): SecurityAlert
    {
        $alert->update([
            $status === 'acknowledged' ? 'acknowledged_at' : 'closed_at' => now(),
            'status' => $status,
        ]);

        return $alert->fresh();
    }
}
