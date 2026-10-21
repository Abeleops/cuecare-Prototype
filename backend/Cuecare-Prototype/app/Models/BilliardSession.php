<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tracks a table session's timing and its shared ledger transaction. */
class BilliardSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function table()
    {
        return $this->belongsTo(BilliardTable::class, 'billiard_table_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source');
    }
}
