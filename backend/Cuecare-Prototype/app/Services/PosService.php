<?php

namespace App\Services;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Posts POS sales, their payments and stock deductions, and reconciles shifts. */
class PosService
{
    /** Creates a sale from the supplied order and payment data, returning its loaded relations. */
    public function createSale(array $data, $user): Sale
    {
        return DB::transaction(function () use ($data, $user) {
            $shift = CashShift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift' => 'Open a cashier shift before charging. Start one from the POS screen.',
                ]);
            }

            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $tax = 0;
            $total = $subtotal;
            $method = $data['payment_method'];
            $received = $method === 'cash' ? (float) ($data['amount_received'] ?? 0) : $total;

            if ($method === 'cash' && $received < $total) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Cash received must cover the total sale.',
                ]);
            }

            $proofPath = null;

            if ($method === 'e-wallet' && ! empty($data['proof_of_payment'])) {
                // The local disk points to storage/app/private and is intentionally not public.
                $proofPath = $data['proof_of_payment']->store('payment-proofs', 'local');
            }

            $sale = Sale::create([
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($data['items'] as $item) {
                $sale->items()->create([
                    'menu_item_id' => $item['menu_item_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'custom_recipe_usage' => $item['custom_recipe_usage']
                        ?? (($data['custom_usage'][$item['menu_item_id'] ?? ''] ?? null)),
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $ledger = $sale->transaction()->create([
                'user_id' => $user->id,
                'shift_id' => $shift?->id,
                'customer_id' => $sale->customer_id,
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'posted',
            ]);
            $sale->payments()->create([
                'transaction_id' => $ledger->id,
                'method' => $method,
                'amount' => $total,
                'amount_received' => $received,
                'change_amount' => max(0, $received - $total),
                'proof_of_payment_path' => $proofPath,
            ]);
            app(InventoryService::class)->deductForSale($sale, $user);

            if ($sale->customer_id) {
                app(LoyaltyService::class)->earn(Customer::findOrFail($sale->customer_id), $ledger, $user);
            }

            return $sale->load('items', 'payments', 'transaction');
        });
    }

    /** Reconciles the supplied shift against counted cash and returns the closed shift. */
    public function closeShift(CashShift $shift, float $counted): CashShift
    {
        $cashSales = app(ReportingService::class)->cashSalesForShift($shift);
        $expected = is_null($shift->starting_cash) ? null : $shift->starting_cash + $cashSales;
        $shift->update([
            'expected_cash' => $expected,
            'counted_cash' => $counted,
            'variance' => is_null($expected) ? null : $counted - $expected,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return $shift;
    }
}
