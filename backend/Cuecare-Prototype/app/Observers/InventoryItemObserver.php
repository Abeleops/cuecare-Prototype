<?php

namespace App\Observers;

use App\Models\InventoryItem;
use App\Services\AuditService;

/** Records inventory item creation and deletion in the audit trail. */
class InventoryItemObserver
{
    /** Audits the new item's initial stock values and unit. */
    public function created(InventoryItem $item): void
    {
        app(AuditService::class)->record(
            'inventory.created',
            $item,
            null,
            $item->only([
                'name',
                'quantity',
                'reorder_level',
                'unit',
            ]),
        );
    }

    /** Audits the deleted item's original values. */
    public function deleted(InventoryItem $item): void
    {
        app(AuditService::class)->record(
            'inventory.deleted',
            $item,
            $item->getOriginal(),
            null,
        );
    }
}
