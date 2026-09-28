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
        Schema::create(LiteCrm::table('pipelines'), function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(LiteCrm::table('pipeline_stages'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pipeline_id')->constrained(LiteCrm::table('pipelines'))->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->unsignedTinyInteger('probability')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['pipeline_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('pipeline_stages'));
        Schema::dropIfExists(LiteCrm::table('pipelines'));
    }
};
