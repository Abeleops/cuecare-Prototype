<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds requester and customer or table details to food orders. */
return new class extends Migration
{
    /** Adds the original requester reference and order location fields. */
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->foreignId('requested_by')
                ->nullable()
                ->after('dining_spot_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('table_or_customer')
                ->nullable()
                ->after('requested_by');
        });
    }

    /** Removes the requester reference and order location field. */
    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropColumn('table_or_customer');
        });
    }
};
