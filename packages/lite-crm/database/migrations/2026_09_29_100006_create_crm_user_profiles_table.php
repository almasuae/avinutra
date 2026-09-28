<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

/*
 * CRM data about a user lives here, so the package never alters the host's
 * users table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(LiteCrm::table('user_profiles'), function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('job_title')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('time_zone', 64)->default('UTC');
            $table->string('phone', 50)->nullable();
            $table->string('whatsapp', 50)->nullable();
            $table->foreignId('territory_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('receives_digest')->default(true);
            $table->timestamp('invited_at')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('user_profiles'));
    }
};
