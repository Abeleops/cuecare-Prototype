<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Provide sample audit records. @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Return the default attributes for a demo record.
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'action' => 'sample.audit',
            'auditable_type' => User::class,
            'auditable_id' => 1,
            'old_values' => [
                'status' => 'old',
            ],
            'new_values' => [
                'status' => 'new',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CUECARE demo',
            'created_at' => now(),
        ];
    }
}
