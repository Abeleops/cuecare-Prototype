<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Links a food order to its items, requesting account, and confirmed sale. */
class FoodOrder extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(FoodOrderItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
