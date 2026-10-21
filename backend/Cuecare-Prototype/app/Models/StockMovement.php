<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Records an append-only inventory movement and its associated approval. */
class StockMovement extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        // Stock movements are the permanent inventory ledger. Corrections use
        // another movement instead of changing or deleting this row.
        static::updating(fn () => throw new \LogicException('Stock movements are append-only.'));
        static::deleting(fn () => throw new \LogicException('Stock movements are append-only.'));
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }
}
