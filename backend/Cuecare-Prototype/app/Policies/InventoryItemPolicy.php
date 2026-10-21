<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

/** Authorizes inventory record changes and custom recipe usage. */
class InventoryItemPolicy
{
    /** Returns whether the supplied user may create an inventory item. */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /** Returns whether the supplied user may update the given inventory item. */
    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('admin');
    }

    /** Returns whether the supplied user may delete the given inventory item. */
    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasRole('admin');
    }

    /** Returns whether the supplied user may customize recipe stock usage. */
    public function customizeUsage(User $user): bool
    {
        return $user->hasPermissionTo('stock.customize_usage');
    }
}
