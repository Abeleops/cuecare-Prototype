<?php

namespace App\Policies;

use App\Models\User;

/** Controls access to the audit log viewer. */
class AuditLogPolicy
{
    /** Returns whether the supplied user may view audit entries. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('audit.view');
    }
}
