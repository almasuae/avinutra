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
        Schema::create(LiteCrm::table('tasks'), function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('taskable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('priority', 10)->default('normal');
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assignee_id', 'status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('tasks'));
    }
};
