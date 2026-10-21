<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Removes legacy discount, vehicle make, and stock reduction request data. */
return new class extends Migration
{
    /** Applies the original Module L column and request data removals. */
    public function up(): void
    {
        foreach ([
            'sales',
            'transactions',
        ] as $table) {
            if (Schema::hasColumn($table, 'discount')) {
                Schema::table($table, fn ($t) => $t->dropColumn('discount'));
            }
        }

        if (Schema::hasColumn('vehicles', 'make')) {
            Schema::table('vehicles', fn ($t) => $t->dropColumn('make'));
        }

        if (Schema::hasTable('approval_requests')) {
            $column = Schema::hasColumn('approval_requests', 'action_type')
                ? 'action_type'
                : (Schema::hasColumn('approval_requests', 'request_type') ? 'request_type' : null);

            if ($column) {
                DB::table('approval_requests')
                    ->whereIn($column, [
                        'stock_reduction',
                        'stock reduction',
                        'issue',
                        'adjustment',
                    ])
                    ->delete();
            }
        }
    }

    /** Leaves the original one-way removals in place. */
    public function down(): void
    {
        // This migration intentionally has no reverse operation.
    }
};
