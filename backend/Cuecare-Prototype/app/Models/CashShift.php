<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores a cashier's shift, cash counts, and reconciliation amounts. */
class CashShift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'starting_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'shift_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'shift_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
