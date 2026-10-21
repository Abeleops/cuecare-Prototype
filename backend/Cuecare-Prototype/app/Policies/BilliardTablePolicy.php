<?php

namespace App\Policies;

use App\Models\BilliardTable;
use App\Models\User;

/** Restricts billiard table creation and deletion to administrators. */
class BilliardTablePolicy
{
    /** Returns whether the supplied user may create a billiard table. */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /** Returns whether the supplied user may delete the given billiard table. */
    public function delete(User $user, BilliardTable $table): bool
    {
        return $user->hasRole('admin');
    }
}
