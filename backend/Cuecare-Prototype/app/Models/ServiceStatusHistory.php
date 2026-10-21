<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Preserves the append-only history of autocare status changes. */
class ServiceStatusHistory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    // Status history is an audit trail and must remain append-only.
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Service status history is append-only.'));
        static::deleting(fn () => throw new \LogicException('Service status history is append-only.'));
    }

    public function booking()
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
