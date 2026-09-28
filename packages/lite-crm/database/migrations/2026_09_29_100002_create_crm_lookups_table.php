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
        Schema::create(LiteCrm::table('lookups'), function (Blueprint $table): void {
            $table->id();
            $table->string('type', 50);
            $table->string('key', 100);
            $table->string('label');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'key']);
            $table->index(['type', 'is_active', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('lookups'));
    }
};
