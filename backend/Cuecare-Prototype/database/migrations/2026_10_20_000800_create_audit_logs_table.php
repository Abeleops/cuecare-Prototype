<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates the general audit trail table. */
return new class extends Migration
{
    /** Creates audit entries with their original fields and indexes. */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $t->string('action');
            $t->string('auditable_type');
            $t->unsignedBigInteger('auditable_id');
            $t->json('old_values')->nullable();
            $t->json('new_values')->nullable();
            $t->ipAddress('ip_address')->nullable();
            $t->text('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index('user_id');
            $t->index('action');
            $t->index([
                'auditable_type',
                'auditable_id',
            ]);
            $t->index('created_at');
        });
    }

    /** Drops the audit logs table. */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
