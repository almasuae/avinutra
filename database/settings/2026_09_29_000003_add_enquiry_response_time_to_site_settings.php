<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
 * The response-time promise in the enquiry acknowledgement e-mail, approved by
 * the owner on 29 Sep 2026 (CONTENT-GAPS #12). Empty = no promise is made.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.enquiry_response_time', 'within one working day');
    }
};
