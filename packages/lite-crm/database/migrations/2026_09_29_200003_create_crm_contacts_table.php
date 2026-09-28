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
        Schema::create(LiteCrm::table('contacts'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->index();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->string('preferred_channel', 20)->nullable();
            $table->json('languages')->nullable();
            $table->string('time_zone', 64)->nullable();
            $table->string('consent_basis', 30)->nullable();
            $table->date('consent_date')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists(LiteCrm::table('contacts'));
    }
};
