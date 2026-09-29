<?php

declare(strict_types=1);

namespace LiteCrm;

use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use LiteCrm\Console\CreateAdminCommand;
use LiteCrm\Console\InstallCommand;
use LiteCrm\CustomFields\CustomFieldRegistry;
use LiteCrm\CustomFields\CustomFieldValidator;
use LiteCrm\Events\EnquiryAssigned;
use LiteCrm\Events\EnquiryCaptured;
use LiteCrm\Events\TaskAssigned;
use LiteCrm\Filament\Pages\AcceptInvitation;
use LiteCrm\Listeners\NotifyEnquiryAssignee;
use LiteCrm\Listeners\NotifyNewEnquiry;
use LiteCrm\Listeners\NotifyTaskAssignee;
use LiteCrm\Listeners\RecordLogin;
use LiteCrm\Livewire\EnquiryForm;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Announcement;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Decision;
use LiteCrm\Models\Document;
use LiteCrm\Models\Enquiry;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Organisation;
use LiteCrm\Models\PriceEntry;
use LiteCrm\Models\Product;
use LiteCrm\Models\Quotation;
use LiteCrm\Models\Sample;
use LiteCrm\Models\Task;
use LiteCrm\Models\Trial;
use LiteCrm\Policies\ActivityPolicy;
use LiteCrm\Policies\AnnouncementPolicy;
use LiteCrm\Policies\ContactPolicy;
use LiteCrm\Policies\DecisionPolicy;
use LiteCrm\Policies\DocumentPolicy;
use LiteCrm\Policies\EnquiryPolicy;
use LiteCrm\Policies\OpportunityPolicy;
use LiteCrm\Policies\OrganisationPolicy;
use LiteCrm\Policies\PriceEntryPolicy;
use LiteCrm\Policies\ProductPolicy;
use LiteCrm\Policies\QuotationPolicy;
use LiteCrm\Policies\SamplePolicy;
use LiteCrm\Policies\TaskPolicy;
use LiteCrm\Policies\TrialPolicy;
use LiteCrm\Support\CrmSettings;
use Livewire\Livewire;

class LiteCrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/lite-crm.php'), 'lite-crm');

        // Per request, so long-running workers never serve stale definitions or settings.
        $this->app->scoped(CustomFieldRegistry::class);
        $this->app->scoped(CustomFieldValidator::class);
        $this->app->scoped(CrmSettings::class);

        // After every provider has registered (and merged its config), before any boots.
        $this->app->booting(function (): void {
            $this->prefixThirdPartyTables();
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'lite-crm');
        $this->loadViewsFrom($this->packagePath('resources/views'), 'lite-crm');
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        // Admins hold every permission (v5 §D1), except hard deletion, which nobody has.
        Gate::before(function (mixed $user, string $ability): ?bool {
            if (str_starts_with($ability, 'forceDelete')) {
                return null;
            }

            return LiteCrm::isSuperAdmin($user) ? true : null;
        });

        foreach ([
            Organisation::class => OrganisationPolicy::class,
            Contact::class => ContactPolicy::class,
            Activity::class => ActivityPolicy::class,
            Task::class => TaskPolicy::class,
            Document::class => DocumentPolicy::class,
            Enquiry::class => EnquiryPolicy::class,
            Product::class => ProductPolicy::class,
            Opportunity::class => OpportunityPolicy::class,
            Sample::class => SamplePolicy::class,
            Trial::class => TrialPolicy::class,
            Quotation::class => QuotationPolicy::class,
            PriceEntry::class => PriceEntryPolicy::class,
            Announcement::class => AnnouncementPolicy::class,
            Decision::class => DecisionPolicy::class,
        ] as $model => $policy) {
            Gate::policy(LiteCrm::model($model), $policy);
        }

        Relation::morphMap(LiteCrm::morphMap());

        Event::listen(Login::class, RecordLogin::class);
        Event::listen(TaskAssigned::class, NotifyTaskAssignee::class);
        Event::listen(EnquiryCaptured::class, NotifyNewEnquiry::class);
        Event::listen(EnquiryAssigned::class, NotifyEnquiryAssignee::class);

        RateLimiter::for('lite-crm-enquiry-api', fn (Request $request): Limit => Limit::perMinute((int) config('lite-crm.enquiry_api.requests_per_minute', 30))->by((string) $request->ip()));
        $this->loadRoutesFrom($this->packagePath('routes/api.php'));

        Livewire::component('lite-crm.accept-invitation', AcceptInvitation::class);
        Livewire::component('lite-crm.enquiry-form', EnquiryForm::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CreateAdminCommand::class,
            ]);

            $this->publishes([
                $this->packagePath('config/lite-crm.php') => config_path('lite-crm.php'),
            ], 'lite-crm-config');

            $this->publishes([
                $this->packagePath('resources/lang') => $this->app->langPath('vendor/lite-crm'),
            ], 'lite-crm-lang');
        }
    }

    /**
     * Store roles, permissions and the audit log in crm_-prefixed tables, unless
     * the host shares its own (lite-crm.prefix_third_party_tables = false).
     */
    protected function prefixThirdPartyTables(): void
    {
        if (! config('lite-crm.prefix_third_party_tables', true)) {
            return;
        }

        config([
            'permission.table_names' => [
                'roles' => LiteCrm::table('roles'),
                'permissions' => LiteCrm::table('permissions'),
                'model_has_permissions' => LiteCrm::table('model_has_permissions'),
                'model_has_roles' => LiteCrm::table('model_has_roles'),
                'role_has_permissions' => LiteCrm::table('role_has_permissions'),
            ],
            'activitylog.table_name' => LiteCrm::table('activity_log'),
        ]);
    }

    public static function packagePath(string $path = ''): string
    {
        return dirname(__DIR__).($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
    }
}
