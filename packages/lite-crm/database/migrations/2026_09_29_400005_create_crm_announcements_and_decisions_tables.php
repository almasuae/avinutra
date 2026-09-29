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
        Schema::create(LiteCrm::table('announcements'), function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->boolean('pinned')->default(false);
            // Role names that see it; null = everyone.
            $table->json('audience_roles')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create(LiteCrm::table('decisions'), function (Blueprint $table): void {
            $table->id();
            $table->date('decided_on')->index();
            $table->string('title');
            $table->text('decision');
            $table->text('rationale')->nullable();
            $table->string('decided_by')->nullable();
            $table->nullableMorphs('related');
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('decisions'));
        Schema::dropIfExists(LiteCrm::table('announcements'));
    }
};
