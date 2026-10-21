<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Links a POS sale to its items, payments, shift, and ledger transaction. */
class Sale extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shift()
    {
        return $this->belongsTo(CashShift::class, 'shift_id');
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source');
    }
}
