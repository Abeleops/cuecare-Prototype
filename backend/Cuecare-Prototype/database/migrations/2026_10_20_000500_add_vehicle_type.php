<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the vehicle type field. */
return new class extends Migration
{
    /** Adds the vehicle type column with its original default. */
    public function up(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->string('vehicle_type')
            ->after('customer_id')
            ->default('car'));
    }

    /** Removes the vehicle type column. */
    public function down(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('vehicle_type'));
    }
};
