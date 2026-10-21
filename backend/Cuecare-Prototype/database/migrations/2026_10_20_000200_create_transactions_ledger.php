<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates the shared transaction ledger and payment link. */
return new class extends Migration
{
    /** Creates ledger entries and adds the transaction reference to payments. */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('cash_shifts');
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('status')->default('posted');
            $table->timestamps();
            $table->index([
                'source_type',
                'source_id',
            ]);
            $table->index([
                'shift_id',
                'created_at',
            ]);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('transaction_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('transactions');
        });
    }

    /** Removes the payment reference and transaction ledger. */
    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('transaction_id'));
        Schema::dropIfExists('transactions');
    }
};
