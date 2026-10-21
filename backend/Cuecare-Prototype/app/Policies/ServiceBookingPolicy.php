<?php

namespace App\Policies;

use App\Models\ServiceBooking;
use App\Models\User;

/** Authorizes autocare booking creation, status changes, and deletion. */
class ServiceBookingPolicy
{
    /** Returns whether the supplied user may create an autocare booking. */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('autocare.access');
    }

    /** Returns whether the supplied user may update the given booking. */
    public function update(User $user, ServiceBooking $booking): bool
    {
        return $user->hasPermissionTo('management.manage');
    }

    /** Returns whether the supplied user may delete the given booking. */
    public function delete(User $user, ServiceBooking $booking): bool
    {
        return $user->hasPermissionTo('management.manage');
    }
}
