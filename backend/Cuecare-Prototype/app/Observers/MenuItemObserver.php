<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Services\AuditService;

/** Records menu item creation, updates, price changes, and deletion. */
class MenuItemObserver
{
    /** Audits the new menu item's initial values. */
    public function created(MenuItem $item): void
    {
        app(AuditService::class)->record(
            'menu.created',
            $item,
            null,
            $item->only([
                'menu_category_id',
                'name',
                'price',
                'available',
                'picture_path',
            ]),
        );
    }

    /** Audits the menu item's old and new values, identifying price changes. */
    public function updated(MenuItem $item): void
    {
        $old = $item->getOriginal();
        $new = $item->getAttributes();
        $action = $item->wasChanged('price') ? 'menu.price_changed' : 'menu.updated';

        app(AuditService::class)->record($action, $item, $old, $new);
    }

    /** Audits the deleted menu item's original values. */
    public function deleted(MenuItem $item): void
    {
        app(AuditService::class)->record(
            'menu.deleted',
            $item,
            $item->getOriginal(),
            null,
        );
    }
}
