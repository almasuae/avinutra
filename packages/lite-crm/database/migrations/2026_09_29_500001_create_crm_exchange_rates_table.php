<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LiteCrm\LiteCrm;

/*
 * Admin-maintained exchange rates, used to total values in the base currency.
 * "rate" is the number of base-currency units for one unit of "currency".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(LiteCrm::table('exchange_rates'), function (Blueprint $table): void {
            $table->id();
            $table->string('currency', 3);
            $table->decimal('rate', 18, 8);
            $table->date('valid_from');
            $table->string('source')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['currency', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(LiteCrm::table('exchange_rates'));
    }
};
