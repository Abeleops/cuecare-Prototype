<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Preserve this migration's existing schema operations.
 */
return new class extends Migration
{
    /**
     * Apply the schema changes without altering existing migration behavior.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'customers',
            'cash_shifts',
            'sales',
            'payments',
            'billiard_tables',
            'billiard_sessions',
            'reservations',
            'vehicles',
            'service_bookings',
            'menu_categories',
            'menu_items',
            'dining_spots',
            'food_orders',
            'inventory_items',
            'security_events',
            'security_rules',
            'security_alerts',
            'transactions',
            'audit_logs',
            'approval_requests',
            'stock_movements',
            'loyalty_transactions',
            'service_catalogs',
            'service_status_histories',
        ];
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = Schema::getColumnListing($table);
            $created = in_array('created_at', $columns, true);
            $updated = in_array('updated_at', $columns, true);
            if (! $created && ! $updated) {
                continue;
            }
            if ($created && $updated) {
                DB::table($table)
                    ->where(fn ($q) => $q->whereNull('created_at')
                        ->orWhereNull('updated_at'))
                    ->update([
                        'created_at' => DB::raw('COALESCE(created_at, updated_at, CURRENT_TIMESTAMP)'),
                        'updated_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)'),
                    ]);
            } elseif ($created) {
                DB::table($table)
                    ->whereNull('created_at')
                    ->update([
                        'created_at' => now(),
                    ]);
            } else {
                DB::table($table)
                    ->whereNull('updated_at')
                    ->update([
                        'updated_at' => now(),
                    ]);
            }
            Schema::table($table, function (Blueprint $b) use ($created, $updated) {
                if ($created) {
                    $b->timestamp('created_at')
                        ->useCurrent()
                        ->change();
                }
                if ($updated) {
                    $b->timestamp('updated_at')
                        ->useCurrent()
                        ->change();
                }
            });
        }
    }

    /**
     * Leave timestamp hardening in place when rolling back.
     */
    public function down(): void
    {
        // Timestamp hardening is intentionally retained.
    }
};
