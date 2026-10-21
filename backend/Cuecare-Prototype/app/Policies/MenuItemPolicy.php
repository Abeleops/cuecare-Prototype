<?php

namespace App\Policies;

use App\Models\MenuItem;
use App\Models\User;

/** Authorizes menu management through the existing management permission. */
class MenuItemPolicy
{
    /** Returns whether the supplied user may view menu management. */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('management.manage');
    }

    /** Returns whether the supplied user may create a menu item. */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('management.manage');
    }

    /** Returns whether the supplied user may update the given menu item. */
    public function update(User $user, MenuItem $item): bool
    {
        return $user->hasPermissionTo('management.manage');
    }

    /** Returns whether the supplied user may delete the given menu item. */
    public function delete(User $user, MenuItem $item): bool
    {
        return $user->hasPermissionTo('management.manage');
    }
}
