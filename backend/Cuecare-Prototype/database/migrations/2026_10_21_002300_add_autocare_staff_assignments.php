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
        Schema::create('service_booking_staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_booking_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->timestamps();
            $t->unique([
                'service_booking_id',
                'user_id',
            ]);
            $t->index('user_id');
        });
    }

    /**
     * Revert the schema changes defined by this migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_staff');
    }
};
