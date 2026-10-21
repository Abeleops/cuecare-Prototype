<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates database-backed sessions when the table is absent. */
return new class extends Migration
{
    /** Creates the sessions table unless it already exists. */
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /** Drops the sessions table. */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
