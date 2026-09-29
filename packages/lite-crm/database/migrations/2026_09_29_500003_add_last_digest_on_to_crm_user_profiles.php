<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

/*
 * The local date of the last daily digest, so an hourly run never sends two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(LiteCrm::table('user_profiles'), function (Blueprint $table): void {
            $table->date('last_digest_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table(LiteCrm::table('user_profiles'), function (Blueprint $table): void {
            $table->dropColumn('last_digest_on');
        });
    }
};
