<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a food order line and links it to its menu item. */
class FoodOrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
