<?php

namespace App\Policies;

use App\Models\User;

/** Authorizes sale creation and viewing through the POS permission. */
class SalePolicy
{
    /** Returns whether the supplied user may create a sale. */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('pos.access');
    }

    /** Returns whether the supplied user may view sales. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('pos.access');
    }
}
