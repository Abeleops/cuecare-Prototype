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
        Schema::table('transactions', function (Blueprint $t) {
            $t->foreignId('reversal_of_id')
                ->nullable()
                ->unique()
                ->constrained('transactions')
                ->restrictOnDelete();
            $t->foreignId('approval_request_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->restrictOnDelete();
        });
        Schema::table('approval_requests', function (Blueprint $t) {
            $t->string('reason_code')->nullable();
        });
    }

    /**
     * Revert the schema changes defined by this migration.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('reversal_of_id');
            $t->dropConstrainedForeignId('approval_request_id');
        });
        Schema::table('approval_requests', fn (Blueprint $t) => $t->dropColumn('reason_code'));
    }
};
