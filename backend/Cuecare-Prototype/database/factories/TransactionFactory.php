<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Provide Transaction Factory data.
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Return the default attributes for a demo record.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_type' => 'demo',
            'source_id' => fake()->unique()->numberBetween(1, 1000000),
            'subtotal' => 100,
            'tax' => 0,
            'total' => 100,
            'status' => 'posted',
        ];
    }
}
