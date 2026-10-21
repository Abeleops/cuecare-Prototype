<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\FoodOrder;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Verify Pos Module H behavior.
 */
class PosModuleHTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Gcash requires private proof and stores it.
     */
    public function test_gcash_requires_private_proof_and_stores_it(): void
    {
        Storage::fake('local');
        $user = User::factory()->create([
            'role' => 'cashier',
        ]);
        CashShift::create([
            'user_id' => $user->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $payload = [
            'items' => [
                [
                    'description' => 'Drink',
                    'quantity' => 1,
                    'unit_price' => 100,
                ],
            ],
            'payment_method' => 'e-wallet',
        ];
        $this->actingAs($user)
            ->post(route('pos.sale'), $payload)
            ->assertSessionHasErrors('proof_of_payment');
        $file = UploadedFile::fake()->image('e-wallet.png');
        $this->actingAs($user)
            ->post(route('pos.sale'), $payload + [
                'proof_of_payment' => $file,
            ])
            ->assertRedirect();
        $payment = Payment::first();
        $this->assertSame('e-wallet', $payment->method);
        $this->assertNotEmpty($payment->proof_of_payment_path);
        Storage::disk('local')->assertExists($payment->proof_of_payment_path);
    }

    /**
     * Cashier confirms or rejects food order requests.
     */
    public function test_cashier_confirms_or_rejects_food_order_requests(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        CashShift::create([
            'user_id' => $cashier->id,
            'starting_cash' => 0,
            'opened_at' => now(),
        ]);
        $requester = User::factory()->create([
            'role' => 'autocare_staff',
        ]);
        $category = MenuCategory::create([
            'name' => 'Snacks',
        ]);
        $item = MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Fries',
            'price' => 80,
            'available' => true,
        ]);
        $order = FoodOrder::create([
            'requested_by' => $requester->id,
            'table_or_customer' => 'Table 1',
            'status' => 'pending',
            'total' => 80,
        ]);
        $order->items()->create([
            'menu_item_id' => $item->id,
            'quantity' => 1,
            'unit_price' => 80,
        ]);
        $this->actingAs($cashier)
            ->patch(route('orders.confirm', $order), [
                'payment_method' => 'card',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('food_orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
        $this->assertNotNull($order->fresh()->sale_id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'food_order.confirmed',
            'auditable_id' => $order->id,
        ]);
        $rejected = FoodOrder::create([
            'requested_by' => $requester->id,
            'table_or_customer' => 'Customer 2',
            'status' => 'pending',
            'total' => 80,
        ]);
        $this->actingAs($cashier)
            ->patch(route('orders.reject', $rejected), [
                'reason' => 'Item unavailable',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('food_orders', [
            'id' => $rejected->id,
            'status' => 'rejected',
            'rejected_reason' => 'Item unavailable',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'food_order.rejected',
            'auditable_id' => $rejected->id,
        ]);
    }
}
