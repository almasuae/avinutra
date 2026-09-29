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
        Schema::create(LiteCrm::table('samples'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained(LiteCrm::table('contacts'))->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained(LiteCrm::table('products'))->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained(LiteCrm::table('opportunities'))->nullOnDelete();
            $table->string('lot_number')->nullable();
            $table->string('quantity')->nullable();
            $table->date('sent_on')->nullable();
            $table->string('courier')->nullable();
            $table->string('tracking')->nullable();
            $table->date('received_on')->nullable();
            $table->text('feedback')->nullable();
            $table->json('custom')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(LiteCrm::table('trials'), function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained(LiteCrm::table('products'))->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained(LiteCrm::table('opportunities'))->nullOnDelete();
            $table->text('protocol')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('result_summary')->nullable();
            $table->boolean('consent_to_publish')->default(false);
            $table->date('consent_date')->nullable();
            $table->json('custom')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('trials'));
        Schema::dropIfExists(LiteCrm::table('samples'));
    }
};
