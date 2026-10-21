<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Records loyalty point activity and its customer, account, and transaction. */
class LoyaltyTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'redemption_value' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
