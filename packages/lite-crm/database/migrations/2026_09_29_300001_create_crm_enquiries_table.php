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
        Schema::create(LiteCrm::table('enquiries'), function (Blueprint $table): void {
            $table->id();
            $table->foreignId('type_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->string('status', 20)->default('new')->index();
            $table->string('name')->nullable();
            $table->string('company')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->foreignId('organisation_id')->nullable()->constrained(LiteCrm::table('organisations'))->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained(LiteCrm::table('contacts'))->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('converted_by')->nullable();
            $table->string('spam_reason', 50)->nullable();
            $table->string('channel', 20)->default('form');
            // A keyed hash of the sender's IP address: enough to spot abuse, not to identify anyone.
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('enquiries'));
    }
};
