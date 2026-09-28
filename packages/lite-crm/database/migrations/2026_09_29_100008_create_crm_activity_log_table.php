<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The audit log table for spatie/laravel-activitylog, named from
 * config('activitylog.table_name') (crm_activity_log by default). Skipped when
 * the host already has it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $name = (string) config('activitylog.table_name');

        if (Schema::hasTable($name)) {
            return;
        }

        Schema::create($name, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('activitylog.table_name'));
    }
};
