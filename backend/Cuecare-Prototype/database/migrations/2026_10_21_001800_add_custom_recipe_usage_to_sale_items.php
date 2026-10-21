<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds custom recipe usage data to sale lines. */
return new class extends Migration
{
    /** Adds the nullable custom usage JSON column. */
    public function up(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->json('custom_recipe_usage')
            ->nullable()
            ->after('line_total'));
    }

    /** Removes the custom usage JSON column. */
    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $table) => $table->dropColumn('custom_recipe_usage'));
    }
};
