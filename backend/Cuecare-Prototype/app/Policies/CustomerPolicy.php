<?php

namespace App\Policies;

use App\Models\User;

/** Authorizes customer loyalty viewing and points redemption. */
class CustomerPolicy
{
    /** Returns whether the supplied user may view customer loyalty records. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('loyalty.view');
    }

    /** Returns whether the supplied user may redeem loyalty points. */
    public function redeem(User $user): bool
    {
        return $user->hasPermissionTo('loyalty.redeem');
    }
}
