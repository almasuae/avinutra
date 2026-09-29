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
        // Gap-free, per-year counters (e.g. quotation numbers).
        Schema::create(LiteCrm::table('sequences'), function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['name', 'year']);
        });

        Schema::create(LiteCrm::table('quotations'), function (Blueprint $table): void {
            $table->id();
            $table->string('number', 50)->unique();
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained(LiteCrm::table('contacts'))->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained(LiteCrm::table('products'))->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained(LiteCrm::table('opportunities'))->nullOnDelete();
            $table->decimal('quantity', 15, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->decimal('price', 15, 4)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('incoterm', 3)->nullable();
            $table->string('port')->nullable();
            $table->string('payment_terms')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('contracting_entity')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(LiteCrm::table('price_entries'), function (Blueprint $table): void {
            $table->id();
            $table->date('observed_on')->index();
            $table->foreignId('product_id')->nullable()->constrained(LiteCrm::table('products'))->nullOnDelete();
            $table->foreignId('source_organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->string('source_type', 30);
            $table->string('basis', 10);
            $table->string('location')->nullable();
            $table->decimal('price', 15, 4);
            $table->string('currency', 3);
            $table->string('unit', 30);
            $table->date('valid_until')->nullable();
            $table->string('reference')->nullable();
            $table->string('confidence', 20)->default('reported');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'basis', 'observed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('price_entries'));
        Schema::dropIfExists(LiteCrm::table('quotations'));
        Schema::dropIfExists(LiteCrm::table('sequences'));
    }
};
