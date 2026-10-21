<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Requests and reviews transaction voids using append-only reversing entries. */
class VoidService
{
    /** Creates a void request for the supplied transaction and actor, returning the request. */
    public function request(Transaction $transaction, User $actor, array $data): ApprovalRequest
    {
        Gate::forUser($actor)->authorize('requestVoid', $transaction);

        return DB::transaction(function () use ($transaction, $actor, $data) {
            $original = Transaction::whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $original->reversal_of_id
                || $original->total <= 0
                || $original->reversal()->exists()
                || $original->voidRequests()
                    ->where('status', 'pending')
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'void' => 'This transaction already has a reversal or pending void request.',
                ]);
            }

            $request = $original->voidRequests()->create([
                'requester_id' => $actor->id,
                'request_type' => 'transaction_void',
                'quantity' => 0,
                'reason_code' => $data['reason_code'],
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
            ]);
            app(AuditService::class)->record(
                'void.requested',
                $request,
                null,
                $request->only([
                    'approvable_id',
                    'reason_code',
                    'reason',
                ]),
                $actor->id,
            );

            return $request;
        });
    }

    /** Approves the supplied request and returns its reversing ledger transaction. */
    public function approve(ApprovalRequest $request, User $actor): Transaction
    {
        Gate::forUser($actor)->authorize('approveVoid', $request);

        return DB::transaction(function () use ($request, $actor) {
            $request = ApprovalRequest::whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($request->status === 'pending', 409, 'This request has already been reviewed.');
            $original = Transaction::whereKey($request->approvable_id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_if($original->reversal_of_id || $original->reversal()->exists(), 409, 'Transaction already reversed.');
            $request->update([
                'status' => 'approved',
                'reviewer_id' => $actor->id,
                'reviewed_at' => now(),
            ]);
            app(AuditService::class)->record(
                'void.approved',
                $request,
                [
                    'status' => 'pending',
                ],
                [
                    'status' => 'approved',
                ],
                $actor->id,
            );

            // Append a signed reversal; never mutate the original transaction or payment.
            $reversal = Transaction::create([
                'user_id' => $actor->id,
                'shift_id' => $original->shift_id,
                'customer_id' => $original->customer_id,
                'source_type' => Transaction::class,
                'source_id' => $original->id,
                'reversal_of_id' => $original->id,
                'approval_request_id' => $request->id,
                'subtotal' => -$original->subtotal,
                'tax' => -$original->tax,
                'total' => -$original->total,
                'status' => 'reversal',
            ]);

            foreach ($original->payments as $payment) {
                $reversal->payments()->create([
                    'method' => $payment->method,
                    'amount' => -$payment->amount,
                    'amount_received' => 0,
                    'change_amount' => 0,
                ]);
            }

            if ($original->shift && $original->shift->status === 'closed') {
                $shift = $original->shift;
                $expected = $shift->starting_cash === null
                    ? null
                    : (float) $shift->starting_cash + app(ReportingService::class)->cashSalesForShift($shift);
                $shift->update([
                    'expected_cash' => $expected,
                    'variance' => $expected === null || $shift->counted_cash === null
                        ? null
                        : (float) $shift->counted_cash - $expected,
                ]);
            }

            app(AuditService::class)->record(
                'void.executed',
                $original,
                null,
                [
                    'reversal_id' => $reversal->id,
                    'approved_by' => $actor->id,
                    'approved_at' => now()->toIso8601String(),
                ],
                $actor->id,
            );

            return $reversal;
        });
    }

    /** Denies the supplied request with a reason, without posting a ledger reversal. */
    public function deny(ApprovalRequest $request, User $actor, string $reason): void
    {
        Gate::forUser($actor)->authorize('approveVoid', $request);

        DB::transaction(function () use ($request, $actor, $reason) {
            $request = ApprovalRequest::whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($request->status === 'pending', 409, 'This request has already been reviewed.');

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'denial_reason' => 'A denial reason is required.',
                ]);
            }

            $request->update([
                'status' => 'denied',
                'reviewer_id' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $reason,
            ]);
            app(AuditService::class)->record(
                'void.denied',
                $request,
                [
                    'status' => 'pending',
                ],
                [
                    'status' => 'denied',
                    'denial_reason' => $reason,
                ],
                $actor->id,
            );
        });
    }
}
