<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/** Controls permission to request transaction voids. */
class TransactionPolicy
{
    /** Returns whether the supplied user may request a void for the given transaction. */
    public function requestVoid(User $user, Transaction $transaction): bool
    {
        return $user->hasPermissionTo('void.request');
    }
}
