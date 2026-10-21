<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Stores an append-only ledger entry and links it to payments and reversals. */
class Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Transactions are append-only. Use a reversing transaction.'));
        static::deleting(fn () => throw new \LogicException('Transactions are append-only. Use a reversing transaction.'));
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shift()
    {
        return $this->belongsTo(CashShift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function originalTransaction()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function voidRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable')
            ->where('request_type', 'transaction_void');
    }
}
