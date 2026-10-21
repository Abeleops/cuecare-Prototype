<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Links an autocare booking to its vehicle, staff, and protected timeline. */
class ServiceBooking extends Model
{
    protected $guarded = [];

    private static bool $allowTimelineMutation = false;

    protected $casts = [
        'scheduled_date' => 'date',
        'received_at' => 'datetime',
        'in_progress_at' => 'datetime',
        'ready_for_pickup_at' => 'datetime',
        'released_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $booking) {
            $timeline = [
                'status',
                'received_at',
                'in_progress_at',
                'ready_for_pickup_at',
                'released_at',
            ];

            if (! self::$allowTimelineMutation && $booking->isDirty($timeline)) {
                throw new \LogicException('Service status timeline is system-managed.');
            }
        });
    }

    /** Runs the supplied timeline update and returns its result, restoring the guard afterward. */
    public static function updateTimeline(callable $callback): mixed
    {
        self::$allowTimelineMutation = true;

        try {
            return $callback();
        } finally {
            self::$allowTimelineMutation = false;
        }
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceCatalog()
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source');
    }

    public function statusHistories()
    {
        return $this->hasMany(ServiceStatusHistory::class);
    }

    public function assignedStaff()
    {
        return $this->belongsToMany(User::class, 'service_booking_staff');
    }
}
