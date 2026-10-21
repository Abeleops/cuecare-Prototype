<?php

namespace App\Policies;

use App\Models\CashShift;
use App\Models\User;

/** Authorizes shift creation and closing the user's own shift. */
class CashShiftPolicy
{
    /** Returns whether the supplied user may start a shift. */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shift.manage');
    }

    /** Returns whether the user may close the supplied shift they own. */
    public function close(User $user, CashShift $shift): bool
    {
        return $user->hasPermissionTo('shift.manage')
            && $shift->user_id === $user->id;
    }
}
