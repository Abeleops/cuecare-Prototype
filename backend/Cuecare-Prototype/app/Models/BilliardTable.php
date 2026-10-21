<?php

namespace App\Models;

use App\Observers\BilliardTableObserver;
use Illuminate\Database\Eloquent\Model;

/** Represents a physical billiard table and registers its audit observer. */
class BilliardTable extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::observe(BilliardTableObserver::class);
    }

    public function sessions()
    {
        return $this->hasMany(BilliardSession::class);
    }
}
