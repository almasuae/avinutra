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
        Schema::create(LiteCrm::table('tags'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });

        Schema::create(LiteCrm::table('taggables'), function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained(LiteCrm::table('tags'))->cascadeOnDelete();
            $table->morphs('taggable');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('taggables'));
        Schema::dropIfExists(LiteCrm::table('tags'));
    }
};
