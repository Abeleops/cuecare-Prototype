<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the account lock timestamp. */
return new class extends Migration
{
    /** Adds the nullable lock timestamp to users. */
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->timestamp('locked_at')
            ->nullable()
            ->after('station'));
    }

    /** Removes the account lock timestamp. */
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('locked_at'));
    }
};
