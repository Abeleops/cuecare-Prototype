<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Stores payment amounts and links them to a sale and ledger transaction. */
class Payment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
