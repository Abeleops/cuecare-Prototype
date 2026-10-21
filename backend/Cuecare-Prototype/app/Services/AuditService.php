<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

/** Writes audit entries and formats their stored values for display. */
class AuditService
{
    /** Records an action and its before/after values, returning the audit entry. */
    public function record(
        string $action,
        object $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): AuditLog {
        $console = app()->runningInConsole() && ! auth()->check();
        $actorId = $userId ?? (auth()->check()
            ? auth()->id()
            : User::where('email', 'system@cuecare.test')->value('id'));

        return AuditLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $console ? null : request()->ip(),
            'user_agent' => $console ? 'console' : request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /** Converts supplied role or permission values to names and returns their audit entry. */
    public function recordPermissionChange(string $action, object $model, mixed $values): AuditLog
    {
        $names = collect(is_iterable($values) ? $values : [$values])
            ->map(fn ($v) => is_object($v) && method_exists($v, 'getRoleName')
                ? $v->getRoleName()
                : (is_object($v) && method_exists($v, 'getPermissionName')
                    ? $v->getPermissionName()
                    : (string) $v))
            ->values()
            ->all();

        return $this->record($action, $model, null, [
            'items' => $names,
        ]);
    }

    /** Returns readable stored values, showing changes against supplied old values. */
    public function formatValues(?array $values, ?array $oldValues = null): string
    {
        if (empty($values)) {
            return '';
        }

        $lines = [];

        foreach ($values as $key => $value) {
            $label = $this->label((string) $key);
            $current = $this->plainValue($value);

            if ($oldValues !== null && array_key_exists($key, $oldValues) && $oldValues[$key] !== $value) {
                $lines[] = $label.': '.$this->plainValue($oldValues[$key]).' → '.$current;
            } else {
                $lines[] = $label.': '.$current;
            }
        }

        return implode("\n", $lines);
    }

    private function label(string $key): string
    {
        return [
            'hourly_rate' => 'Rate per hour',
            'price_per_half_hour' => 'Rate per half hour',
            'locked_at' => 'Locked at',
            'rejected_reason' => 'Rejection reason',
            'sale_id' => 'Sale ID',
        ][$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    private function plainValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(
                fn ($item) => is_array($item)
                    ? implode(', ', array_map('strval', $item))
                    : strval($item),
                $value,
            ));
        }

        if ($value === null) {
            return '—';
        }

        return (string) $value;
    }
}
