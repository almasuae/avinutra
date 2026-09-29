<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Website content managed in CRM › Website (v5 §E2 host-only admin resources).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_seo', function (Blueprint $table): void {
            $table->id();
            $table->string('route_name', 150)->unique();
            $table->string('title', 70)->nullable();
            $table->string('description', 170)->nullable();
            $table->timestamps();
        });

        Schema::create('team_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role_type', 20); // adviser, employee, consultant
            $table->string('job_title')->nullable();
            $table->string('qualification')->nullable();
            $table->string('university')->nullable();
            $table->unsignedSmallInteger('qualification_year')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->text('specialisations')->nullable();
            $table->text('bio')->nullable();
            $table->json('publications')->nullable();
            $table->string('languages')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('consent_on_file')->default(false);
            $table->date('consent_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category', 40);
            $table->string('status', 20)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->text('outline')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('team_profiles')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('team_profiles')->nullOnDelete();
            $table->json('sources')->nullable();
            $table->string('related_route', 150)->nullable();
            $table->date('last_reviewed_on')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('glossary_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('term');
            $table->string('slug')->unique();
            $table->text('definition');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category', 60)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('calculator_defaults', function (Blueprint $table): void {
            $table->id();
            $table->string('tool', 60);
            $table->string('key', 80);
            $table->string('label');
            $table->decimal('value', 18, 6)->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('source')->nullable();
            $table->date('source_date')->nullable();
            $table->string('approved_by')->nullable();
            $table->date('approved_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['tool', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_defaults');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('glossary_terms');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('team_profiles');
        Schema::dropIfExists('page_seo');
    }
};
