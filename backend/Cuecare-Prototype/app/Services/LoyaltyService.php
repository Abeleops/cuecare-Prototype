<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Earns and redeems customer points with a corresponding loyalty ledger entry. */
class LoyaltyService
{
    /** Returns the points entry earned for a transaction, or null when no points accrue. */
    public function earn(Customer $customer, Transaction $transaction, User $user): ?LoyaltyTransaction
    {
        $points = (int) floor((float) $transaction->total / 100);

        if ($points < 1) {
            return null;
        }

        return DB::transaction(function () use ($customer, $transaction, $user, $points) {
            $locked = Customer::whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();
            $balance = (int) $locked->loyalty_points + $points;
            $locked->update([
                'loyalty_points' => $balance,
            ]);

            return LoyaltyTransaction::create([
                'customer_id' => $locked->id,
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $balance,
            ]);
        });
    }

    /** Redeems the supplied points and returns the resulting loyalty ledger entry. */
    public function redeem(Customer $customer, int $points, User $user, ?string $notes = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($customer, $points, $user, $notes) {
            $locked = Customer::whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->loyalty_points < $points) {
                throw ValidationException::withMessages([
                    'points' => 'Customer does not have enough loyalty points.',
                ]);
            }

            $balance = (int) $locked->loyalty_points - $points;
            $locked->update([
                'loyalty_points' => $balance,
            ]);

            return LoyaltyTransaction::create([
                'customer_id' => $locked->id,
                'user_id' => $user->id,
                'type' => 'redeem',
                'points' => $points,
                'balance_after' => $balance,
                'redemption_value' => $points,
                'notes' => $notes,
            ]);
        });
    }
}
