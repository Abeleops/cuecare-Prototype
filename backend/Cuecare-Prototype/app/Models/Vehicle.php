<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Links a vehicle to its autocare service bookings. */
class Vehicle extends Model
{
    protected $guarded = [];

    public function bookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }
}
