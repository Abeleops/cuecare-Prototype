<?php

namespace App\Models;

use App\Observers\InventoryItemObserver;
use Illuminate\Database\Eloquent\Model;

/** Tracks stock levels, recipes, movements, and approval requests. */
class InventoryItem extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::observe(InventoryItemObserver::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function getLowStockAttribute()
    {
        return $this->quantity <= $this->reorder_level;
    }
}
