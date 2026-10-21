<?php

namespace App\Policies;

use App\Models\SecurityAlert;
use App\Models\User;

/** Authorizes viewing and managing security alerts. */
class SecurityAlertPolicy
{
    /** Returns whether the supplied user may view security alerts. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('security.view');
    }

    /** Returns whether the supplied user may update the given security alert. */
    public function update(User $user, SecurityAlert $alert): bool
    {
        return $user->hasPermissionTo('security.manage');
    }
}
