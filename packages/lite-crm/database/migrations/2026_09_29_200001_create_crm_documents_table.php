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
        Schema::create(LiteCrm::table('documents'), function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('documentable');
            $table->string('title');
            $table->foreignId('type_id')->nullable()->constrained(LiteCrm::table('lookups'))->nullOnDelete();
            $table->string('disk', 50);
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('issuer')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->boolean('verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_method')->nullable();
            $table->boolean('confidential')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('documents'));
    }
};
