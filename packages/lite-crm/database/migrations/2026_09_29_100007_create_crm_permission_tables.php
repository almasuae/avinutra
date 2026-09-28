<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tables for spatie/laravel-permission, named from config('permission.table_names')
 * (crm_-prefixed by default; see lite-crm.prefix_third_party_tables). Skipped when
 * the host already has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        /** @var array<string, string> $tables */
        $tables = config('permission.table_names');
        /** @var array<string, string|null> $columns */
        $columns = config('permission.column_names');
        $pivotRole = $columns['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columns['permission_pivot_key'] ?? 'permission_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        if (Schema::hasTable($tables['permissions'])) {
            return;
        }

        Schema::create($tables['permissions'], function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['roles'], function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tables['model_has_permissions'], function (Blueprint $table) use ($tables, $pivotPermission, $morphKey): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($morphKey);
            $table->index([$morphKey, 'model_type'], 'crm_mhp_model_id_model_type_index');

            $table->foreign($pivotPermission)->references('id')->on($tables['permissions'])->cascadeOnDelete();
            $table->primary([$pivotPermission, $morphKey, 'model_type'], 'crm_mhp_permission_model_type_primary');
        });

        Schema::create($tables['model_has_roles'], function (Blueprint $table) use ($tables, $pivotRole, $morphKey): void {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($morphKey);
            $table->index([$morphKey, 'model_type'], 'crm_mhr_model_id_model_type_index');

            $table->foreign($pivotRole)->references('id')->on($tables['roles'])->cascadeOnDelete();
            $table->primary([$pivotRole, $morphKey, 'model_type'], 'crm_mhr_role_model_type_primary');
        });

        Schema::create($tables['role_has_permissions'], function (Blueprint $table) use ($tables, $pivotRole, $pivotPermission): void {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)->references('id')->on($tables['permissions'])->cascadeOnDelete();
            $table->foreign($pivotRole)->references('id')->on($tables['roles'])->cascadeOnDelete();
            $table->primary([$pivotPermission, $pivotRole], 'crm_rhp_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        /** @var array<string, string> $tables */
        $tables = config('permission.table_names');

        Schema::dropIfExists($tables['role_has_permissions']);
        Schema::dropIfExists($tables['model_has_roles']);
        Schema::dropIfExists($tables['model_has_permissions']);
        Schema::dropIfExists($tables['roles']);
        Schema::dropIfExists($tables['permissions']);
    }
};
