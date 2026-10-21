<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the half-hour billiard table rate. */
return new class extends Migration
{
    /** Adds the existing half-hour pricing column and default. */
    public function up(): void
    {
        Schema::table('billiard_tables', function (Blueprint $table) {
            $table->decimal('price_per_half_hour', 12, 2)
                ->default(100)
                ->after('hourly_rate');
        });
    }

    /** Removes the half-hour pricing column. */
    public function down(): void
    {
        Schema::table('billiard_tables', fn (Blueprint $table) => $table->dropColumn('price_per_half_hour'));
    }
};
