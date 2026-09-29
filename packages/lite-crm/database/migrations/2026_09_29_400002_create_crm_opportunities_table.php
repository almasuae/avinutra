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
        // Restrict a pipeline to some roles (null = everyone with access to opportunities).
        Schema::table(LiteCrm::table('pipelines'), function (Blueprint $table): void {
            $table->json('visible_to_roles')->nullable()->after('is_active');
        });

        Schema::create(LiteCrm::table('opportunities'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('pipeline_id')->constrained(LiteCrm::table('pipelines'));
            $table->foreignId('stage_id')->constrained(LiteCrm::table('pipeline_stages'));
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained(LiteCrm::table('contacts'))->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained(LiteCrm::table('products'))->nullOnDelete();
            $table->decimal('volume', 15, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->unsignedTinyInteger('probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('next_step')->nullable();
            $table->date('next_step_date')->nullable();
            $table->foreignId('lost_reason_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->text('notes')->nullable();
            $table->json('custom')->nullable();
            $table->foreignId('enquiry_id')->nullable()->constrained(LiteCrm::table('enquiries'))->nullOnDelete();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pipeline_id', 'stage_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('opportunities'));

        Schema::table(LiteCrm::table('pipelines'), function (Blueprint $table): void {
            $table->dropColumn('visible_to_roles');
        });
    }
};
