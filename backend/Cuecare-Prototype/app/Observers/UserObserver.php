<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditService;

/** Records account creation, lock changes, and deletion in the audit trail. */
class UserObserver
{
    /** Audits the new account's identifying details. */
    public function created(User $user): void
    {
        app(AuditService::class)->record(
            'user.created',
            $user,
            null,
            $user->only([
                'name',
                'email',
                'role',
                'station',
            ]),
        );
    }

    /** Audits changes to the supplied account's lock state. */
    public function updated(User $user): void
    {
        if ($user->wasChanged('locked_at')) {
            app(AuditService::class)->record(
                $user->locked_at ? 'user.locked' : 'user.unlocked',
                $user,
                [
                    'locked_at' => $user->getOriginal('locked_at'),
                ],
                [
                    'locked_at' => $user->locked_at,
                ],
            );
        }
    }

    /** Audits the deleted account's original values. */
    public function deleted(User $user): void
    {
        app(AuditService::class)->record(
            'user.deleted',
            $user,
            $user->getOriginal(),
            null,
        );
    }
}
