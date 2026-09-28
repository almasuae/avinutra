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
        Schema::create(LiteCrm::table('organisations'), function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('type_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->string('country', 100)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('territory_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('permission_to_name_publicly')->default(false);
            $table->date('permission_granted_on')->nullable();
            $table->foreignId('permission_document_id')->nullable()->constrained(LiteCrm::table('documents'))->nullOnDelete();
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
        Schema::dropIfExists(LiteCrm::table('organisations'));
    }
};
