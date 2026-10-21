<?php

namespace Database\Factories;

use App\Models\ApprovalRequest;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Provide Approval Request Factory data.
 */
class ApprovalRequestFactory extends Factory
{
    protected $model = ApprovalRequest::class;

    /**
     * Return the default attributes for a demo record.
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory()->state([
                'role' => 'cashier',
            ]),
            'approvable_type' => Transaction::class,
            'approvable_id' => Transaction::factory(),
            'request_type' => 'transaction_void',
            'quantity' => 0,
            'reason_code' => 'data entry error',
            'reason' => 'Demonstration request',
            'status' => 'pending',
        ];
    }
}
