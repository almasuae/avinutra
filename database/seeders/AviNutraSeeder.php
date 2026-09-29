<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Settings\SiteSettings;
use Illuminate\Database\Seeder;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Lookup;
use LiteCrm\Presets\PresetLoader;

/**
 * AviNutra's CRM set-up: the feed-additives preset, each enquiry type routed
 * to its mailbox from the site settings, the Knowledge Centre launch content,
 * and the content gaps as CRM tasks. Safe to run again.
 *
 * Never seeds users.
 */
class AviNutraSeeder extends Seeder
{
    /**
     * Enquiry type key => key in SiteSettings::$emails.
     */
    public const MAILBOXES = [
        'general' => 'info',
        'ask_nutritionist' => 'nutrition',
        'sourcing_request' => 'sales',
        'quotation' => 'sales',
        'sample' => 'sales',
        'document' => 'sales',
        'supplier_application' => 'partners',
    ];

    public function run(PresetLoader $presets, SiteSettings $site): void
    {
        $presets->apply($presets->load('feed-additives'));

        /** @var class-string<Lookup> $model */
        $model = LiteCrm::model(Lookup::class);

        foreach (self::MAILBOXES as $type => $mailbox) {
            $address = $site->emails[$mailbox] ?? null;
            $lookup = $model::query()->where('type', 'enquiry_type')->where('key', $type)->first();

            if ($lookup === null || ! is_string($address) || $address === '') {
                continue;
            }

            $lookup->update(['meta' => [...($lookup->meta ?? []), 'mailbox' => $address]]);
        }

        $this->call([KnowledgeSeeder::class, CalculatorDefaultSeeder::class, ContentGapTaskSeeder::class]);
    }
}
