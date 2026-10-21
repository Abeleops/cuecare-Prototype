<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (! Schema::hasColumn('menu_items', 'picture_path')) {
            Schema::table('menu_items', fn (Blueprint $t) => $t->string('picture_path')
                ->nullable()
                ->after('price'));
        }
    }

    /**
     * Revert the schema changes defined by this migration.
     */
    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'picture_path')) {
            Schema::table('menu_items', fn (Blueprint $t) => $t->dropColumn('picture_path'));
        }
    }
};
