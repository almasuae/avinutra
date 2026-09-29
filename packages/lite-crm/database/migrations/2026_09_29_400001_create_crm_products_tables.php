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
        Schema::create(LiteCrm::table('products'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->text('description')->nullable();
            $table->json('specification')->nullable();
            $table->string('packaging')->nullable();
            $table->string('storage')->nullable();
            $table->string('shelf_life')->nullable();
            $table->string('availability', 20)->default('information');
            $table->boolean('publish_on_website')->default(false);
            $table->json('custom')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['publish_on_website', 'availability']);
        });

        Schema::create(LiteCrm::table('product_suppliers'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained(LiteCrm::table('products'))->cascadeOnDelete();
            $table->foreignId('organisation_id')->constrained(LiteCrm::table('organisations'))->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'organisation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('product_suppliers'));
        Schema::dropIfExists(LiteCrm::table('products'));
    }
};
