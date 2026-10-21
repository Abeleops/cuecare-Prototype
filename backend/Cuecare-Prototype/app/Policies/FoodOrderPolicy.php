<?php

namespace App\Policies;

use App\Models\User;

/** Authorizes food order requests and counter confirmation. */
class FoodOrderPolicy
{
    /** Returns whether the supplied user may submit a food order request. */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('orders.request');
    }

    /** Returns whether the supplied user may confirm a food order. */
    public function confirm(User $user): bool
    {
        return $user->hasPermissionTo('pos.access');
    }
}
