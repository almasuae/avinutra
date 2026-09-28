<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use LiteCrm\LiteCrm;
use LiteCrm\LiteCrmPlugin;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\Permissions;

/**
 * CRM-wide settings: MFA for everyone, and the labels shown for each role.
 *
 * @property-read Schema $form
 */
class CrmSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'crm-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Permissions::allows(Filament::auth()->user(), 'settings.manage');
    }

    public static function getNavigationGroup(): ?string
    {
        return LiteCrmPlugin::settingsNavigationGroup();
    }

    public static function getNavigationLabel(): string
    {
        return __('lite-crm::settings.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return __('lite-crm::settings.title');
    }

    public function mount(): void
    {
        $settings = app(CrmSettings::class);

        $this->form->fill([
            'mfa_required_for_all' => $settings->isMfaRequiredForAll(),
            'role_labels' => (array) $settings->get(CrmSettings::ROLE_LABELS, []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $roleInputs = [];

        foreach (LiteCrm::roleModel()::query()->orderBy('id')->pluck('name') as $role) {
            $roleInputs[] = TextInput::make("role_labels.{$role}")
                ->label($role)
                ->placeholder(app(CrmSettings::class)->roleLabel((string) $role))
                ->maxLength(100);
        }

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('lite-crm::settings.sections.security'))
                    ->schema([
                        Toggle::make('mfa_required_for_all')
                            ->label(__('lite-crm::settings.fields.mfa_required_for_all'))
                            ->helperText(__('lite-crm::settings.fields.mfa_required_for_all_help')),
                    ]),
                Section::make(__('lite-crm::settings.sections.role_labels'))
                    ->description(__('lite-crm::settings.sections.role_labels_help'))
                    ->schema($roleInputs)
                    ->columns(2),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label(__('lite-crm::settings.save'))->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $settings = app(CrmSettings::class);

        $settings->set(CrmSettings::MFA_REQUIRED_FOR_ALL, (bool) ($data['mfa_required_for_all'] ?? false));
        $settings->set(CrmSettings::ROLE_LABELS, array_filter((array) ($data['role_labels'] ?? []), 'filled'));

        Notification::make()->title(__('lite-crm::settings.saved'))->success()->send();
    }
}
