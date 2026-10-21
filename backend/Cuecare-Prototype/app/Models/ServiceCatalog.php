<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores an autocare service's catalog price and availability. */
class ServiceCatalog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }
}
