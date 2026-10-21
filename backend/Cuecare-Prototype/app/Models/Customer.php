<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Links a customer to sales, vehicles, and loyalty activity. */
class Customer extends Model
{
    protected $guarded = [];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
