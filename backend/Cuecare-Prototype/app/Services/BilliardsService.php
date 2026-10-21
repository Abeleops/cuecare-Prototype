<?php

namespace App\Services;

use App\Models\BilliardSession;
use App\Models\BilliardTable;
use App\Models\CashShift;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Starts table sessions and posts their timed charges to the ledger. */
class BilliardsService
{
    /** Starts a session for the supplied table and optional customer, returning the session. */
    public function start(BilliardTable $table, ?int $customerId = null): BilliardSession
    {
        return DB::transaction(function () use ($table, $customerId) {
            $session = $table->sessions()->create([
                'customer_id' => $customerId,
                'started_at' => now(),
                'status' => 'active',
            ]);
            $table->update([
                'status' => 'occupied',
            ]);

            return $session;
        });
    }

    /** Ends the supplied session, posts its charge, and returns the refreshed session. */
    public function end(BilliardSession $session): BilliardSession
    {
        return DB::transaction(function () use ($session) {
            $shift = CashShift::where('user_id', auth()->id())
                ->where('status', 'open')
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift' => 'Open a cashier shift before charging a billiards session. Start one from the POS screen.',
                ]);
            }

            $session->load('table');
            $endedAt = now();
            $minutes = max(1, intdiv($session->started_at->diffInSeconds($endedAt), 60));
            $amount = round(($minutes / 60) * $session->table->hourly_rate, 2);
            $session->update([
                'ended_at' => $endedAt,
                'amount' => $amount,
                'status' => 'completed',
            ]);
            $session->table->update([
                'status' => 'available',
            ]);
            $session->transaction()->create([
                'user_id' => auth()->id(),
                'customer_id' => $session->customer_id,
                'source_type' => BilliardSession::class,
                'source_id' => $session->id,
                'subtotal' => $amount,
                'tax' => 0,
                'total' => $amount,
                'status' => 'posted',
            ]);

            return $session->fresh('table', 'transaction');
        });
    }
}
