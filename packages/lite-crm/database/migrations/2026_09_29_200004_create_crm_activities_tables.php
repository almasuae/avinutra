<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

/*
 * Interactions with customers and partners (calls, meetings ...), not the audit
 * log (crm_activity_log).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(LiteCrm::table('activities'), function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('subject');
            $table->foreignId('type_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->dateTime('occurred_at')->index();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->text('summary');
            $table->text('outcome')->nullable();
            $table->string('next_step')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(LiteCrm::table('activity_participants'), function (Blueprint $table): void {
            $table->foreignId('activity_id')->constrained(LiteCrm::table('activities'))->cascadeOnDelete();
            $table->morphs('participant');

            $table->primary(['activity_id', 'participant_type', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('activity_participants'));
        Schema::dropIfExists(LiteCrm::table('activities'));
    }
};
