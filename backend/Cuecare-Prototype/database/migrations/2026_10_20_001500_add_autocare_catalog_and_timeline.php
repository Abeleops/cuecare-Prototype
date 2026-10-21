<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the autocare service catalog and status timeline. */
return new class extends Migration
{
    /** Creates the catalog and history tables and extends service bookings. */
    public function up(): void
    {
        Schema::create('service_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 12, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->foreignId('service_catalog_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('service_catalogs')
                ->nullOnDelete();
            $table->timestamp('received_at')
                ->nullable()
                ->after('status');
            $table->timestamp('in_progress_at')
                ->nullable()
                ->after('received_at');
            $table->timestamp('ready_for_pickup_at')
                ->nullable()
                ->after('in_progress_at');
            $table->timestamp('released_at')
                ->nullable()
                ->after('ready_for_pickup_at');
            $table->index([
                'status',
                'scheduled_date',
            ]);
        });
        Schema::create('service_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_booking_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('changed_at');
            $table->index([
                'service_booking_id',
                'changed_at',
            ]);
        });
    }

    /** Removes the history, booking additions, and service catalog. */
    public function down(): void
    {
        Schema::dropIfExists('service_status_histories');
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_catalog_id');
            $table->dropColumn([
                'received_at',
                'in_progress_at',
                'ready_for_pickup_at',
                'released_at',
            ]);
        });
        Schema::dropIfExists('service_catalogs');
    }
};
