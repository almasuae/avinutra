<?php

declare(strict_types=1);

namespace LiteCrm;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rules\Password;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Filament\Pages\AcceptInvitation;
use LiteCrm\Filament\Pages\CrmDashboard;
use LiteCrm\Filament\Pages\CrmSettingsPage;
use LiteCrm\Filament\Pages\EditProfile;
use LiteCrm\Filament\Pages\OpportunityBoard;
use LiteCrm\Filament\Resources\Activities\ActivityResource;
use LiteCrm\Filament\Resources\Announcements\AnnouncementResource;
use LiteCrm\Filament\Resources\AuditLog\AuditLogResource;
use LiteCrm\Filament\Resources\Contacts\ContactResource;
use LiteCrm\Filament\Resources\CustomFields\CustomFieldResource;
use LiteCrm\Filament\Resources\Decisions\DecisionResource;
use LiteCrm\Filament\Resources\Documents\DocumentResource;
use LiteCrm\Filament\Resources\Enquiries\EnquiryResource;
use LiteCrm\Filament\Resources\ExchangeRates\ExchangeRateResource;
use LiteCrm\Filament\Resources\Lookups\LookupResource;
use LiteCrm\Filament\Resources\Opportunities\OpportunityResource;
use LiteCrm\Filament\Resources\Organisations\OrganisationResource;
use LiteCrm\Filament\Resources\Pipelines\PipelineResource;
use LiteCrm\Filament\Resources\PriceLog\PriceEntryResource;
use LiteCrm\Filament\Resources\Products\ProductResource;
use LiteCrm\Filament\Resources\Quotations\QuotationResource;
use LiteCrm\Filament\Resources\Roles\RoleResource;
use LiteCrm\Filament\Resources\Samples\SampleResource;
use LiteCrm\Filament\Resources\Tags\TagResource;
use LiteCrm\Filament\Resources\Tasks\TaskResource;
use LiteCrm\Filament\Resources\Trials\TrialResource;
use LiteCrm\Filament\Resources\Users\UserResource;
use LiteCrm\Http\Controllers\DownloadDocument;
use LiteCrm\Http\Middleware\EnforceSessionLifetime;
use LiteCrm\Http\Middleware\NoIndex;
use LiteCrm\Http\Middleware\RequireMultiFactorAuthentication;

/**
 * Registers the CRM in a host's Filament panel.
 */
class LiteCrmPlugin implements Plugin
{
    /** @var array<string, bool>|null */
    protected ?array $modules = null;

    protected ?string $navigationGroup = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'lite-crm';
    }

    /**
     * Override the module toggles from config('lite-crm.modules') for this panel.
     *
     * @param  array<string, bool>  $modules
     */
    public function modules(array $modules): static
    {
        $this->modules = $modules;

        return $this;
    }

    /**
     * @return array<string, bool>
     */
    public function getModules(): array
    {
        /** @var array<string, bool> $configured */
        $configured = config('lite-crm.modules', []);

        return array_merge($configured, $this->modules ?? []);
    }

    public function isModuleEnabled(string $module): bool
    {
        return (bool) ($this->getModules()[$module] ?? false);
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup ?? __('lite-crm::lite-crm.navigation.group');
    }

    /**
     * The navigation group of record screens on the current panel.
     */
    public static function recordNavigationGroup(): string
    {
        $panel = Filament::getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin('lite-crm')) {
            /** @var static $plugin */
            $plugin = $panel->getPlugin('lite-crm');

            return $plugin->getNavigationGroup();
        }

        return __('lite-crm::lite-crm.navigation.group');
    }

    public static function settingsNavigationGroup(): string
    {
        return __('lite-crm::lite-crm.navigation.settings');
    }

    public function register(Panel $panel): void
    {
        LiteCrm::setPanelId($panel->getId());

        // One source of truth: policies and resources read the effective toggles from config.
        config(['lite-crm.modules' => $this->getModules()]);

        $records = array_keys(array_filter([
            EnquiryResource::class => $this->isModuleEnabled('enquiries'),
            OrganisationResource::class => $this->isModuleEnabled('organisations'),
            ContactResource::class => $this->isModuleEnabled('contacts'),
            ActivityResource::class => $this->isModuleEnabled('activities'),
            TaskResource::class => $this->isModuleEnabled('tasks'),
            DocumentResource::class => $this->isModuleEnabled('documents'),
            OpportunityResource::class => $this->isModuleEnabled('opportunities'),
            ProductResource::class => $this->isModuleEnabled('products'),
            SampleResource::class => $this->isModuleEnabled('samples'),
            TrialResource::class => $this->isModuleEnabled('trials'),
            QuotationResource::class => $this->isModuleEnabled('quotations'),
            PriceEntryResource::class => $this->isModuleEnabled('price_log'),
            AnnouncementResource::class => $this->isModuleEnabled('announcements'),
            DecisionResource::class => $this->isModuleEnabled('decisions'),
        ]));

        $panel
            ->resources([
                ...$records,
                UserResource::class,
                ExchangeRateResource::class,
                RoleResource::class,
                LookupResource::class,
                PipelineResource::class,
                TagResource::class,
                CustomFieldResource::class,
                AuditLogResource::class,
            ])
            ->pages(array_filter([
                CrmDashboard::class,
                CrmSettingsPage::class,
                $this->isModuleEnabled('opportunities') ? OpportunityBoard::class : null,
            ]))
            ->widgets(array_values(CrmDashboard::WIDGETS))
            ->databaseNotifications()
            ->profile(EditProfile::class, isSimple: false)
            // MFA is offered to everyone; RequireMultiFactorAuthentication decides who must set it up.
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()], isRequired: true)
            ->multiFactorAuthenticationRequiredMiddlewareName(RequireMultiFactorAuthentication::class)
            ->routes(function (): void {
                Route::get('invitation/{user}', AcceptInvitation::class)
                    ->middleware(ValidateSignature::class)
                    ->name('lite-crm.invitation');
            })
            // Signed, short-lived and still behind login and a permission check.
            ->authenticatedRoutes(function (): void {
                Route::get('documents/{document}/download', DownloadDocument::class)
                    ->middleware(ValidateSignature::class)
                    ->name('lite-crm.documents.download');
            })
            ->middleware([NoIndex::class], isPersistent: true)
            ->authMiddleware([EnforceSessionLifetime::class], isPersistent: true);
    }

    public function boot(Panel $panel): void
    {
        // Dates are stored in UTC and shown in each user's own time zone.
        FilamentTimezone::set(function (): ?string {
            $user = Filament::auth()->user();

            return $user instanceof CrmUser ? $user->crmTimezone() : null;
        });

        Password::defaults(fn (): Password => Password::min((int) config('lite-crm.auth.password_min_length', 12)));
    }
}
