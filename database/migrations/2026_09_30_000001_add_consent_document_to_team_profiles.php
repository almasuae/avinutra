<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The signed consent document of a team profile, on the private disk (v3 §7.2).
 * A profile can be published only with this document, the consent flag and its date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_profiles', function (Blueprint $table): void {
            $table->string('consent_document_path')->nullable()->after('consent_date');
        });
    }

    public function down(): void
    {
        Schema::table('team_profiles', function (Blueprint $table): void {
            $table->dropColumn('consent_document_path');
        });
    }
};
