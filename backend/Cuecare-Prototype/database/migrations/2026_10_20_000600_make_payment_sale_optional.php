<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Makes a payment's sale reference optional. */
return new class extends Migration
{
    /** Allows null sale references on payments. */
    public function up(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->foreignId('sale_id')
            ->nullable()
            ->change());
    }

    /** Restores the non-null sale reference definition. */
    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->foreignId('sale_id')
            ->nullable(false)
            ->change());
    }
};
