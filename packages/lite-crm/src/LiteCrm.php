<?php

declare(strict_types=1);

namespace LiteCrm;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LiteCrm\Contracts\CrmUser;
use LiteCrm\Enquiries\EnquiryIntake;
use LiteCrm\Enquiries\Submission;
use LiteCrm\Events\EnquiryCaptured;
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
use LiteCrm\Support\CrmSettings;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * Entry point for host applications and for the package itself.
 */
class LiteCrm
{
    protected static string $panelId = 'crm';

    /**
     * The class to use for a package model: the host's subclass when one is
     * mapped in config('lite-crm.models'), otherwise the package model.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $model
     * @return class-string<TModel>
     */
    public static function model(string $model): string
    {
        $class = config("lite-crm.models.{$model}");

        if ($class === null) {
            return $model;
        }

        if (! is_string($class) || ! is_a($class, $model, true)) {
            throw new InvalidArgumentException("lite-crm.models: [{$model}] must map to a subclass of itself.");
        }

        return $class;
    }

    /**
     * @return class-string<Model&CrmUser>
     */
    public static function userModel(): string
    {
        $class = config('lite-crm.user_model');

        if (! is_string($class) || ! is_a($class, Model::class, true) || ! is_a($class, CrmUser::class, true)) {
            throw new InvalidArgumentException('lite-crm.user_model must be an Eloquent model implementing '.CrmUser::class.'.');
        }

        return $class;
    }

    /**
     * @return class-string<Role>
     */
    public static function roleModel(): string
    {
        $class = app(PermissionRegistrar::class)->getRoleClass();

        if (! is_a($class, Role::class, true)) {
            throw new InvalidArgumentException('permission.models.role must extend '.Role::class.'.');
        }

        return $class;
    }

    /**
     * Record models that activities, tasks and documents can be attached to.
     *
     * @return list<class-string<Model>>
     */
    public static function recordModels(): array
    {
        return [
            self::model(Organisation::class),
            self::model(Contact::class),
            self::model(Enquiry::class),
            self::model(Opportunity::class),
            self::model(Product::class),
            self::model(Sample::class),
            self::model(Trial::class),
            self::model(Quotation::class),
        ];
    }

    /**
     * Stable morph aliases, so stored types survive class renames and host subclasses.
     *
     * @return array<string, class-string<Model>>
     */
    public static function morphMap(): array
    {
        return [
            'crm_organisation' => self::model(Organisation::class),
            'crm_contact' => self::model(Contact::class),
            'crm_activity' => self::model(Activity::class),
            'crm_task' => self::model(Task::class),
            'crm_document' => self::model(Document::class),
            'crm_enquiry' => self::model(Enquiry::class),
            'crm_opportunity' => self::model(Opportunity::class),
            'crm_product' => self::model(Product::class),
            'crm_sample' => self::model(Sample::class),
            'crm_trial' => self::model(Trial::class),
            'crm_quotation' => self::model(Quotation::class),
            'crm_price_entry' => self::model(PriceEntry::class),
            'crm_announcement' => self::model(Announcement::class),
            'crm_decision' => self::model(Decision::class),
        ];
    }

    /** @var (Closure(): ?string)|null */
    protected static ?Closure $contractingEntityResolver = null;

    /**
     * Lets the host decide which legal entity contracts on quotations, e.g. from
     * its own settings. Pass null to fall back to lite-crm.quotations.contracting_entity.
     *
     * @param  (Closure(): ?string)|null  $resolver
     */
    public static function resolveContractingEntityUsing(?Closure $resolver): void
    {
        static::$contractingEntityResolver = $resolver;
    }

    public static function contractingEntity(): ?string
    {
        $entity = static::$contractingEntityResolver !== null
            ? (static::$contractingEntityResolver)()
            : config('lite-crm.quotations.contracting_entity');

        return is_string($entity) && $entity !== '' ? $entity : null;
    }

    /**
     * Store an enquiry from host code and fire {@see EnquiryCaptured} (which
     * e-mails the team). Code is trusted: no spam checks apply.
     *
     * @param  array<string, mixed>  $data  name, company, email, phone, country, city, message; other keys go into the payload
     * @param  string  $type  the key of an enquiry type (crm_lookups, type "enquiry_type")
     *
     * @throws ValidationException
     */
    public static function captureEnquiry(array $data, string $type, ?string $sourceUrl = null): Enquiry
    {
        return app(EnquiryIntake::class)->capture($data, $type, $sourceUrl, Submission::trusted(consent: (bool) ($data['consent'] ?? false)));
    }

    /**
     * Active CRM users for select fields, as id => name.
     *
     * @return array<int|string, string>
     */
    public static function userOptions(): array
    {
        /** @var array<int|string, string> $options */
        $options = self::userModel()::query()
            ->whereHas('crmProfile', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->pluck('name', (new (self::userModel()))->getKeyName())
            ->all();

        return $options;
    }

    /**
     * The currencies in use: CRM settings (set by an Admin or a preset), else config.
     *
     * @return list<string>
     */
    public static function currencies(): array
    {
        /** @var list<string> $configured */
        $configured = (array) config('lite-crm.currencies', ['USD']);
        $currencies = app(CrmSettings::class)->get(CrmSettings::CURRENCIES);
        $currencies = is_array($currencies) && $currencies !== [] ? array_values(array_map('strval', $currencies)) : $configured;

        $base = static::baseCurrency();

        return in_array($base, $currencies, true) ? $currencies : [$base, ...$currencies];
    }

    public static function baseCurrency(): string
    {
        $base = app(CrmSettings::class)->get(CrmSettings::BASE_CURRENCY);

        return is_string($base) && $base !== '' ? $base : (string) config('lite-crm.base_currency', 'USD');
    }

    public static function quotationPrefix(): string
    {
        $prefix = app(CrmSettings::class)->get(CrmSettings::QUOTATION_PREFIX);

        return is_string($prefix) && $prefix !== '' ? $prefix : (string) config('lite-crm.quotations.number_prefix', 'Q');
    }

    /**
     * The id of the Filament panel the plugin is registered on.
     */
    public static function panelId(): string
    {
        return static::$panelId;
    }

    public static function setPanelId(string $panelId): void
    {
        static::$panelId = $panelId;
    }

    public static function table(string $name): string
    {
        return config('lite-crm.table_prefix', 'crm_').$name;
    }

    public static function isModuleEnabled(string $module): bool
    {
        return (bool) config("lite-crm.modules.{$module}", false);
    }

    public static function superAdminRole(): string
    {
        return (string) config('lite-crm.auth.super_admin_role', 'admin');
    }

    public static function isSuperAdmin(mixed $user): bool
    {
        return is_object($user)
            && in_array(HasRoles::class, class_uses_recursive($user), true)
            && $user->hasRole(self::superAdminRole());
    }
}
