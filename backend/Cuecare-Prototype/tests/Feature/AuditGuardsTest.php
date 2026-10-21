<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Audit Guards behavior.
 */
class AuditGuardsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Transaction update and delete are rejected and row stays unchanged.
     */
    public function test_transaction_update_and_delete_are_rejected_and_row_stays_unchanged(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'source_type' => 'manual',
            'source_id' => 1,
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
        ]);
        try {
            $transaction->update([
                'total' => 999,
            ]);
        } catch (\LogicException) {
        }
        try {
            $transaction->delete();
        } catch (\LogicException) {
        }
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'total' => 100,
        ]);
    }

    /**
     * Audit logs are append only.
     */
    public function test_audit_logs_are_append_only(): void
    {
        $log = AuditLog::factory()->create();
        $this->expectException(\LogicException::class);
        $log->delete();
    }
}
