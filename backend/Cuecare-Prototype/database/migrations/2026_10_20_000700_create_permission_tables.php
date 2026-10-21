<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Creates role, permission, and assignment tables. */
return new class extends Migration
{
    /** Creates the permission tables and their original keys and constraints. */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $t->unique([
                'name',
                'guard_name',
            ]);
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('guard_name');
            $t->timestamps();
            $t->unique([
                'name',
                'guard_name',
            ]);
        });
        Schema::create('model_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->index([
                'model_id',
                'model_type',
            ]);
            $t->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
            $t->primary([
                'permission_id',
                'model_id',
                'model_type',
            ]);
        });
        Schema::create('model_has_roles', function (Blueprint $t) {
            $t->unsignedBigInteger('role_id');
            $t->string('model_type');
            $t->unsignedBigInteger('model_id');
            $t->index([
                'model_id',
                'model_type',
            ]);
            $t->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $t->primary([
                'role_id',
                'model_id',
                'model_type',
            ]);
        });
        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->unsignedBigInteger('permission_id');
            $t->unsignedBigInteger('role_id');
            $t->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
            $t->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $t->primary([
                'permission_id',
                'role_id',
            ]);
        });
    }

    /** Drops the permission and assignment tables in dependency order. */
    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
