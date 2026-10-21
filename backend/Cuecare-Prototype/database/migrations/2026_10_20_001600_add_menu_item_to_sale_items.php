<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links sale lines to optional menu items. */
return new class extends Migration
{
    /** Adds the menu reference and index to sale items. */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('menu_item_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('menu_items')
                ->nullOnDelete();
            $table->index('menu_item_id');
        });
    }

    /** Removes the menu reference from sale items. */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('menu_item_id');
        });
    }
};
