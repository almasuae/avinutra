<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(LiteCrm::table('custom_fields'), function (Blueprint $table): void {
            $table->id();
            $table->string('entity', 50);
            $table->string('key', 100);
            $table->string('label');
            $table->string('type', 30);
            $table->json('options')->nullable();
            $table->string('section')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->json('visible_for_types')->nullable();
            $table->boolean('show_in_table')->default(false);
            $table->boolean('filterable')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['entity', 'key']);
            $table->index(['entity', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('custom_fields'));
    }
};
