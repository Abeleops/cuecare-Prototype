<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the shift closure reason. */
return new class extends Migration
{
    /** Adds the nullable close reason to cashier shifts. */
    public function up(): void
    {
        Schema::table('cash_shifts', fn (Blueprint $table) => $table->string('close_reason')
            ->nullable()
            ->after('closed_at'));
    }

    /** Removes the shift closure reason. */
    public function down(): void
    {
        Schema::table('cash_shifts', fn (Blueprint $table) => $table->dropColumn('close_reason'));
    }
};
