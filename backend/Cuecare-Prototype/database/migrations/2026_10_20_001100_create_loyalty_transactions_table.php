<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates the customer loyalty activity ledger. */
return new class extends Migration
{
    /** Creates loyalty transactions and their original references and indexes. */
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('type');
            $table->unsignedInteger('points');
            $table->unsignedInteger('balance_after');
            $table->decimal('redemption_value', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index([
                'customer_id',
                'created_at',
            ]);
        });
    }

    /** Drops the loyalty transactions table. */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
