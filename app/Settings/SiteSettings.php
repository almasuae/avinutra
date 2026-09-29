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

    /**
     * The company-status statement (Content Blueprint v3, "Status statement
     * variants"), shown in the footer, on About › Company, on Contact and on
     * quotations. Never claims incorporation before $sg_incorporated is true.
     */
    public function statusStatement(): string
    {
        if ($this->sg_incorporated && filled($this->legal_name)) {
            $statement = filled($this->uen)
                ? "{$this->legal_name} (UEN {$this->uen}) is incorporated in Singapore."
                : "{$this->legal_name} is incorporated in Singapore.";

            return filled($this->pk_partner_name)
                ? "{$statement} In Pakistan, products are imported and supplied through {$this->pk_partner_name}."
                : $statement;
        }

        if (filled($this->pk_partner_name)) {
            $partner = $this->pk_partner_name.(filled($this->pk_partner_city) ? ", {$this->pk_partner_city}" : '');

            return 'Our international trading company is being established in Singapore. Until incorporation is complete, '
                ."commercial activity in Pakistan is conducted through our partner, {$partner}.";
        }

        return 'Our international trading company is being established in Singapore, with commercial activity initially focused on Pakistan.';
    }

    public static function group(): string
    {
        return 'site';
    }
}
