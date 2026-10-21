<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Migrates the former billiards staff role to the existing cashier field. */
return new class extends Migration
{
    /** Updates former staff accounts and removes their old role assignments. */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'billiards_staff')
            ->update([
                'role' => 'cashier',
            ]);
        if (Schema::hasTable('roles')) {
            $roleIds = DB::table('roles')
                ->where('name', 'billiards_staff')
                ->pluck('id');
            if ($roleIds->isNotEmpty()) {
                DB::table('model_has_roles')
                    ->whereIn('role_id', $roleIds)
                    ->delete();
                DB::table('role_has_permissions')
                    ->whereIn('role_id', $roleIds)
                    ->delete();
                DB::table('roles')
                    ->whereIn('id', $roleIds)
                    ->delete();
            }
        }
    }

    /** Leaves the irreversible role migration in place. */
    public function down(): void
    {
        // The original role assignment cannot be reconstructed safely after the
        // migration. Existing role data is intentionally not recreated.
    }
};
