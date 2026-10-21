<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Defines the inventory quantity used by a menu item's recipe. */
class Recipe extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
