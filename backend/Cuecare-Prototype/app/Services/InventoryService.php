<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Records sale deductions and handles the existing inventory approval service. */
class InventoryService
{
    /** Deducts recipe ingredients for the supplied sale and records stock movements. */
    public function deductForSale(Sale $sale, User $user): void
    {
        foreach ($sale->items()
            ->with('menuItem.recipes')
            ->get() as $saleItem) {
            if (! $saleItem->menuItem) {
                continue;
            }

            foreach ($saleItem->menuItem->recipes as $recipe) {
                $customUsage = $saleItem->custom_recipe_usage ?? [];
                $perUnit = array_key_exists((string) $recipe->inventory_item_id, $customUsage)
                    ? (float) $customUsage[$recipe->inventory_item_id]
                    : (float) $recipe->quantity;
                $required = $perUnit * (int) $saleItem->quantity;
                $item = InventoryItem::whereKey($recipe->inventory_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $item->quantity < $required) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$item->name}.",
                    ]);
                }

                $item->decrement('quantity', $required);
                StockMovement::create([
                    'inventory_item_id' => $item->id,
                    'user_id' => $user->id,
                    'approval_request_id' => null,
                    'movement_type' => 'sale_deduction',
                    'quantity' => -$required,
                    'notes' => "Automatic deduction for sale #{$sale->id}",
                ]);
            }
        }
    }

    /** Creates and returns an inventory approval request from the supplied data. */
    public function request(array $data, User $requester): ApprovalRequest
    {
        return ApprovalRequest::create([
            'requester_id' => $requester->id,
            'approvable_type' => InventoryItem::class,
            'approvable_id' => $data['inventory_item_id'],
            'request_type' => $data['request_type'],
            'quantity' => $data['quantity'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    /** Reviews the supplied request, applies approved stock changes, and returns it refreshed. */
    public function review(ApprovalRequest $request, User $reviewer, string $status, ?string $notes = null): ApprovalRequest
    {
        return DB::transaction(function () use ($request, $reviewer, $status, $notes) {
            abort_if($request->status !== 'pending', 409, 'This request has already been reviewed.');
            $request->update([
                'reviewer_id' => $reviewer->id,
                'status' => $status,
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            if ($status === 'approved') {
                $item = InventoryItem::whereKey($request->approvable_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $delta = $request->request_type === 'receipt'
                    ? abs((float) $request->quantity)
                    : ($request->request_type === 'issue'
                        ? -abs((float) $request->quantity)
                        : (float) $request->quantity);
                abort_if($item->quantity + $delta < 0, 422, 'The approved movement would make stock negative.');
                $item->increment('quantity', $delta);
                StockMovement::create([
                    'inventory_item_id' => $item->id,
                    'user_id' => $reviewer->id,
                    'approval_request_id' => $request->id,
                    'movement_type' => $request->request_type,
                    'quantity' => $delta,
                    'notes' => $request->reason,
                ]);
            }

            app(AuditService::class)->record(
                'inventory.approval_'.$status,
                $request,
                [
                    'status' => 'pending',
                ],
                [
                    'status' => $status,
                    'reviewer_id' => $reviewer->id,
                ],
            );

            return $request->fresh([
                'approvable',
                'movement',
            ]);
        });
    }
}
