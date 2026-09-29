<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Website settings (v5 §A5), edited by Admins in CRM › Website › Site settings.
 *
 * Company-status wording is built from these values and never hard-coded.
 * "Pte. Ltd." must not appear anywhere until $sg_incorporated is true.
 */
class SiteSettings extends Settings
{
    public string $brand;

    public string $tagline;

    public string $positioning;

    /** @var array<string, string> Mailboxes keyed by purpose: info, nutrition, sales, partners, noreply. */
    public array $emails;

    public ?string $whatsapp_sales;

    public ?string $whatsapp_nutrition;

    public bool $sg_incorporated;

    public ?string $legal_name;

    public ?string $uen;

    public ?string $registered_office;

    public ?string $pk_partner_name;

    public ?string $pk_partner_city;

    public ?string $pk_partner_role;

    /** Phrase used in the enquiry acknowledgement ("We aim to reply …"); null = no promise. */
    public ?string $enquiry_response_time;

    public static function group(): string
    {
        return 'site';
    }
}
