<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verify Module P behavior.
 */
class ModulePTest extends TestCase
{
    use RefreshDatabase;

    private function sale(User $user): Transaction
    {
        CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 500,
            'opened_at' => now(),
        ]);

        return app(PosService::class)->createSale([
            'items' => [
                [
                    'description' => 'Tea',
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 150,
        ], $user)->transaction;
    }

    /**
     * Shift header and own shift controls.
     */
    public function test_shift_header_and_own_shift_controls(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier);
        $response = $this->get(route('pos'))
            ->assertOk()
            ->assertSee('Closed')
            ->assertSee($cashier->name);
        $this->assertMatchesRegularExpression('/id="end-shift"[^>]*disabled/', $response->getContent());
        $this->post(route('shift.open'), [
            'starting_cash' => 500,
        ])->assertRedirect();
        $this->post(route('shift.open'), [])->assertStatus(409);
        $response = $this->get(route('pos'))
            ->assertOk()
            ->assertSee('Open')
            ->assertSee('Shift start');
        $this->assertMatchesRegularExpression('/id="start-shift"[^>]*disabled/', $response->getContent());
        $this->post(route('shift.close'), [])->assertSessionHasErrors('counted_cash');
        $this->post(route('shift.close'), [
            'counted_cash' => 500,
        ])->assertRedirect();
        $this->assertDatabaseHas('cash_shifts', [
            'user_id' => $cashier->id,
            'status' => 'closed',
        ]);
    }

    /**
     * Shift routes require shift manage.
     */
    public function test_shift_routes_require_shift_manage(): void
    {
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $this->actingAs($staff);
        $this->post(route('shift.open'), [])->assertForbidden();
        $this->post(route('shift.close'), [
            'counted_cash' => 0,
        ])->assertForbidden();
    }

    /**
     * Charge redirects to unsaved blank sale.
     */
    public function test_charge_redirects_to_unsaved_blank_sale(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier);
        CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $this->post(route('pos.sale'), [
            'items' => [
                [
                    'description' => 'Tea',
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
            'payment_method' => 'cash',
            'amount_received' => 150,
        ])->assertRedirect(route('pos'));
        $snapshot = Transaction::first()->getAttributes();
        $this->get(route('pos'))
            ->assertOk()
            ->assertSee('items:[]', false)
            ->assertSee('Back to dashboard');
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, Transaction::count());
        $this->assertSame($snapshot, Transaction::first()->getAttributes());
    }

    /**
     * Cashier requests admin approves with signed cash reversal and audit.
     */
    public function test_cashier_requests_admin_approves_with_signed_cash_reversal_and_audit(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $original = $this->sale($cashier);
        $snapshot = $original->getAttributes();
        $this->actingAs($cashier)
            ->get(route('pos'))
            ->assertSee('Are you sure you want to void transaction #'.$original->id)
            ->assertSee('This cannot be undone.');
        $this->withHeader('User-Agent', 'ModuleP browser')
            ->post(route('voids.store', $original), [
                'reason_code' => 'other',
            ])
            ->assertSessionHasErrors('reason');
        $this->post(route('voids.store', $original), [
            'reason_code' => 'data entry error',
        ])->assertRedirect();
        $approval = ApprovalRequest::where('request_type', 'transaction_void')->firstOrFail();
        $this->assertSame(1, Transaction::count());
        $this->get(route('voids.index'))->assertForbidden();
        $this->post(route('voids.approve', $approval))->assertForbidden();
        $this->post(route('voids.deny', $approval), [
            'denial_reason' => 'No',
        ])->assertForbidden();
        $this->actingAs($admin)
            ->get(route('voids.index'))
            ->assertOk()
            ->assertSee('Pending void approvals');
        $this->post(route('voids.approve', $approval))->assertRedirect();
        $reversal = $original->reversal()->firstOrFail();
        $this->assertEquals(-100, $reversal->total);
        $this->assertSame($admin->id, $reversal->user_id);
        $this->assertEquals(-100, $reversal->payments->sum('amount'));
        $this->assertSame($snapshot, $original->fresh()->getAttributes());
        $this->assertEquals(0, app(ReportingService::class)->cashSalesForShift($original->shift));
        app(PosService::class)->closeShift($original->shift, 500);
        $this->assertEquals(500, $original->shift->fresh()->expected_cash);
        $this->post(route('voids.approve', $approval))->assertStatus(409);
        foreach ([
            'void.requested',
            'void.approved',
            'void.executed',
        ] as $action) {
            $log = AuditLog::where('action', $action)->firstOrFail();
            $this->assertNotNull($log->user_id);
            $this->assertNotNull($log->ip_address);
            $this->assertNotNull($log->user_agent);
        }
        $this->get(route('reports'))
            ->assertOk()
            ->assertSee('Voided')
            ->assertSee($admin->name);
    }

    /**
     * Self approval is forbidden and manager denial does not post.
     */
    public function test_self_approval_is_forbidden_and_manager_denial_does_not_post(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $original = $this->sale($admin);
        $this->actingAs($admin)
            ->post(route('voids.store', $original), [
                'reason_code' => 'damage',
            ])
            ->assertRedirect();
        $approval = ApprovalRequest::where('request_type', 'transaction_void')->firstOrFail();
        $this->post(route('voids.approve', $approval))->assertForbidden();
        $this->actingAs($manager)
            ->post(route('voids.deny', $approval), [])
            ->assertSessionHasErrors('denial_reason');
        $this->post(route('voids.deny', $approval), [
            'denial_reason' => 'Sale was valid',
        ])->assertRedirect();
        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('approval_requests', [
            'id' => $approval->id,
            'status' => 'denied',
            'review_notes' => 'Sale was valid',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'void.denied',
            'user_id' => $manager->id,
        ]);
    }

    /**
     * Void route forbids unpermitted staff and original stays append only.
     */
    public function test_void_route_forbids_unpermitted_staff_and_original_stays_append_only(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $staff = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $original = $this->sale($cashier);
        $this->actingAs($staff)
            ->post(route('voids.store', $original), [
                'reason_code' => 'damage',
            ])
            ->assertForbidden();
        foreach ([
            'update',
            'delete',
        ] as $action) {
            try {
                if ($action === 'update') {
                    $original->update([
                        'total' => 0,
                    ]);
                } else {
                    $original->delete();
                }
                $this->fail('Mutation accepted');
            } catch (\LogicException $e) {
                $this->assertStringContainsString('append-only', $e->getMessage());
            }
        }
        $this->assertDatabaseHas('transactions', [
            'id' => $original->id,
            'total' => 100,
        ]);
    }

    /**
     * Manager approval reconciles closed shift without changing close time.
     */
    public function test_manager_approval_reconciles_closed_shift_without_changing_close_time(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $original = $this->sale($cashier);
        $this->actingAs($cashier);
        app(PosService::class)->closeShift($original->shift, 600);
        $closedAt = $original->shift->fresh()->closed_at->toDateTimeString();
        $this->post(route('voids.store', $original), [
            'reason_code' => 'customer complaint',
        ])->assertRedirect();
        $this->post(route('voids.store', $original), [
            'reason_code' => 'damage',
        ])->assertSessionHasErrors('void');
        $approval = ApprovalRequest::where('request_type', 'transaction_void')->firstOrFail();
        $this->actingAs($manager)
            ->post(route('voids.approve', $approval))
            ->assertRedirect();
        $shift = $original->shift->fresh();
        $this->assertEquals(500, $shift->expected_cash);
        $this->assertEquals(100, $shift->variance);
        $this->assertSame($closedAt, $shift->closed_at->toDateTimeString());
    }

    /**
     * Void factory and card reversal do not reduce cash.
     */
    public function test_void_factory_and_card_reversal_do_not_reduce_cash(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);
        $shift = CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 500,
            'opened_at' => now(),
        ]);
        $original = Transaction::factory()->create([
            'user_id' => $cashier->id,
            'shift_id' => $shift->id,
        ]);
        $original->payments()->create([
            'method' => 'card',
            'amount' => 100,
        ]);
        $approval = ApprovalRequest::factory()->create([
            'requester_id' => $cashier->id,
            'approvable_id' => $original->id,
        ]);
        $this->actingAs($manager)
            ->post(route('voids.approve', $approval))
            ->assertRedirect();
        $this->assertEquals(0, app(ReportingService::class)->cashSalesForShift($shift));
    }
}
