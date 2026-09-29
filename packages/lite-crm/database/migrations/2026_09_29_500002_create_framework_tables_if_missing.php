<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

/*
 * Framework tables the CRM relies on, with their standard names so they are
 * shared with the host: Laravel's database notifications (in-app messages) and
 * Filament's import/export bookkeeping. Each is created only if missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $users = (new (LiteCrm::userModel()))->getTable();

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('imports')) {
            Schema::create('imports', function (Blueprint $table) use ($users): void {
                $table->id();
                $table->timestamp('completed_at')->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('importer');
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('total_rows');
                $table->unsignedInteger('successful_rows')->default(0);
                $table->foreignId('user_id')->constrained($users)->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('exports')) {
            Schema::create('exports', function (Blueprint $table) use ($users): void {
                $table->id();
                $table->timestamp('completed_at')->nullable();
                $table->string('file_disk');
                $table->string('file_name')->nullable();
                $table->string('exporter');
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('total_rows');
                $table->unsignedInteger('successful_rows')->default(0);
                $table->foreignId('user_id')->constrained($users)->cascadeOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('failed_import_rows')) {
            Schema::create('failed_import_rows', function (Blueprint $table): void {
                $table->id();
                $table->json('data');
                $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
                $table->text('validation_error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Shared framework tables are left in place.
    }
};
