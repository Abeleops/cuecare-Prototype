<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates the original operational tables and extends user account fields. */
return new class extends Migration
{
    /** Creates the original CUECARE tables, columns, constraints, and indexes. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')
                ->default('cashier')
                ->index();
            $t->string('station')->nullable();
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->unsignedInteger('loyalty_points')->default(0);
            $t->timestamps();
        });
        Schema::create('cash_shifts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->decimal('starting_cash', 12, 2);
            $t->decimal('expected_cash', 12, 2)->default(0);
            $t->decimal('counted_cash', 12, 2)->nullable();
            $t->decimal('variance', 12, 2)->nullable();
            $t->timestamp('opened_at');
            $t->timestamp('closed_at')->nullable();
            $t->string('status')->default('open');
            $t->timestamps();
            $t->index([
                'user_id',
                'status',
            ]);
        });
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('shift_id')
                ->nullable()
                ->constrained('cash_shifts');
            $t->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $t->decimal('subtotal', 12, 2);
            $t->decimal('tax', 12, 2)->default(0);
            $t->decimal('discount', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('status')->default('paid');
            $t->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->string('description');
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('line_total', 12, 2);
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->string('method');
            $t->decimal('amount', 12, 2);
            $t->timestamps();
        });
        Schema::create('billiard_tables', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('status')->default('available');
            $t->decimal('hourly_rate', 12, 2);
            $t->timestamps();
        });
        Schema::create('billiard_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('billiard_table_id')->constrained();
            $t->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('reservations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $t->string('room');
            $t->date('reserved_date');
            $t->string('time_slot');
            $t->decimal('deposit', 12, 2)->default(0);
            $t->string('status')->default('reserved');
            $t->timestamps();
        });
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $t->string('plate_number')->unique();
            $t->string('make');
            $t->string('model');
            $t->timestamps();
        });
        Schema::create('service_bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')
                ->nullable()
                ->constrained();
            $t->foreignId('vehicle_id')->constrained();
            $t->string('requested_service');
            $t->date('scheduled_date');
            $t->string('status')->default('received');
            $t->decimal('estimated_cost', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('menu_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_category_id')->constrained();
            $t->string('name');
            $t->decimal('price', 12, 2);
            $t->boolean('available')->default(true);
            $t->timestamps();
        });
        Schema::create('dining_spots', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type')->default('table');
            $t->string('qr_token')->unique();
            $t->timestamps();
        });
        Schema::create('food_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('dining_spot_id')
                ->nullable()
                ->constrained();
            $t->string('status')->default('pending');
            $t->decimal('total', 12, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('food_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('food_order_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->foreignId('menu_item_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 12, 2);
        });
        Schema::create('inventory_items', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('quantity', 12, 3);
            $t->decimal('reorder_level', 12, 3);
            $t->string('unit')->default('pcs');
            $t->timestamps();
        });
        Schema::create('recipes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->foreignId('inventory_item_id')
                ->constrained()
                ->cascadeOnDelete();
            $t->decimal('quantity', 12, 3);
            $t->unique([
                'menu_item_id',
                'inventory_item_id',
            ]);
        });
        Schema::create('security_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->nullable()
                ->constrained();
            $t->string('type');
            $t->string('ip_address')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->index([
                'type',
                'created_at',
            ]);
        });
        Schema::create('security_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('event_type');
            $t->unsignedInteger('threshold');
            $t->unsignedInteger('window_minutes');
            $t->string('action')->default('alert');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('security_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('security_rule_id')->constrained();
            $t->string('severity')->default('high');
            $t->string('status')->default('open');
            $t->text('message');
            $t->timestamp('acknowledged_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
        });
    }

    /** Drops the original operational tables and added user columns. */
    public function down(): void
    {
        foreach ([
            'security_alerts',
            'security_rules',
            'security_events',
            'recipes',
            'inventory_items',
            'food_order_items',
            'food_orders',
            'dining_spots',
            'menu_items',
            'menu_categories',
            'service_bookings',
            'vehicles',
            'reservations',
            'billiard_sessions',
            'billiard_tables',
            'payments',
            'sale_items',
            'sales',
            'cash_shifts',
            'customers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn([
            'role',
            'station',
        ]));
    }
};
