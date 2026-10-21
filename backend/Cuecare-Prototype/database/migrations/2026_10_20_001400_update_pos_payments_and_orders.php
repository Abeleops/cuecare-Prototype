<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Updates shift cash declarations, payment details, and order sale links. */
return new class extends Migration
{
    /** Applies the original nullable cash fields and payment and order additions. */
    public function up(): void
    {
        Schema::table('cash_shifts', function (Blueprint $t) {
            $t->decimal('starting_cash', 12, 2)
                ->nullable()
                ->change();
            $t->decimal('expected_cash', 12, 2)
                ->nullable()
                ->change();
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->decimal('change_amount', 12, 2)
                ->default(0)
                ->after('amount');
            $t->decimal('amount_received', 12, 2)
                ->nullable()
                ->after('change_amount');
            $t->string('proof_of_payment_path')
                ->nullable()
                ->after('amount_received');
        });
        Schema::table('food_orders', function (Blueprint $t) {
            $t->foreignId('sale_id')
                ->nullable()
                ->after('total')
                ->constrained('sales')
                ->nullOnDelete();
            $t->text('rejected_reason')
                ->nullable()
                ->after('status');
        });
    }

    /** Removes the order and payment additions defined by the original rollback. */
    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('sale_id');
            $t->dropColumn('rejected_reason');
        });
        Schema::table('payments', function (Blueprint $t) {
            $t->dropColumn([
                'change_amount',
                'amount_received',
                'proof_of_payment_path',
            ]);
        });
    }
};
