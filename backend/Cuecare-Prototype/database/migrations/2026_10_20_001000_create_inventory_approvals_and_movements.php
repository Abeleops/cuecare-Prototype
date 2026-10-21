<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates the original inventory approval and movement tables. */
return new class extends Migration
{
    /** Creates approval requests and stock movements with their original constraints. */
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('reviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->morphs('approvable');
            $table->string('request_type');
            $table->decimal('quantity', 12, 3);
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index([
                'status',
                'created_at',
            ]);
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('approval_request_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('movement_type');
            $table->decimal('quantity', 12, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index([
                'inventory_item_id',
                'created_at',
            ]);
        });
    }

    /** Drops stock movements followed by approval requests. */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('approval_requests');
    }
};
