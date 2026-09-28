<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/*
 * Initial values from v5 §A5. Unknown facts stay null: the site omits whatever
 * depends on them (see CONTENT-GAPS.md).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('site.brand', 'AviNutra');
        $this->migrator->add('site.tagline', 'Where Feed Science Meets Reliable Supply.');
        $this->migrator->add('site.positioning', 'Poultry Feed Experts · Nutrition Consultants · Feed Ingredient Suppliers');
        $this->migrator->add('site.emails', [
            'info' => 'info@avinutra.com',
            'nutrition' => 'nutrition@avinutra.com',
            'sales' => 'sales@avinutra.com',
            'partners' => 'partners@avinutra.com',
            'noreply' => 'noreply@avinutra.com',
        ]);
        $this->migrator->add('site.whatsapp_sales', null);
        $this->migrator->add('site.whatsapp_nutrition', null);
        $this->migrator->add('site.sg_incorporated', false);
        $this->migrator->add('site.legal_name', null);
        $this->migrator->add('site.uen', null);
        $this->migrator->add('site.registered_office', null);
        $this->migrator->add('site.pk_partner_name', null);
        $this->migrator->add('site.pk_partner_city', null);
        $this->migrator->add('site.pk_partner_role', 'importer of record and local sales partner');
    }
};
