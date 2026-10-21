<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\Transaction;
use App\Models\User;

/** Authorizes inventory request reviews and transaction void approvals. */
class ApprovalRequestPolicy
{
    /** Returns whether the supplied user may review inventory approval requests. */
    public function review(User $user, ApprovalRequest $request): bool
    {
        return $user->hasPermissionTo('inventory.approve');
    }

    /** Returns whether the user may approve this void request without self-approval. */
    public function approveVoid(User $user, ApprovalRequest $request): bool
    {
        return $user->hasPermissionTo('void.approve')
            && $request->request_type === 'transaction_void'
            && $request->approvable_type === Transaction::class
            && $request->requester_id !== $user->id;
    }
}
