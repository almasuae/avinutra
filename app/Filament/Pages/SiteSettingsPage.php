<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\WebsiteResource;
use App\Settings\SiteSettings;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use LiteCrm\Support\Permissions;

/**
 * CRM › Website › Site settings (v5 §A5). The company-status values drive quotations
 * in the CRM only; they are never shown on the public website (decision of 29 Sep 2026).
 *
 * @property-read Schema $form
 */
class SiteSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'site-settings';

    protected static ?string $title = 'Site settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Permissions::allows(Filament::auth()->user(), 'website.manage');
    }

    public static function getNavigationGroup(): ?string
    {
        return WebsiteResource::GROUP;
    }

    public function mount(): void
    {
        $this->form->fill(app(SiteSettings::class)->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Brand')
                    ->schema([
                        TextInput::make('brand')->required()->maxLength(100),
                        TextInput::make('tagline')->required()->maxLength(200),
                        TextInput::make('positioning')->required()->maxLength(200),
                    ])
                    ->columns(3),
                Section::make('Mailboxes')
                    ->description('Enquiries are routed to these addresses by type; they are also shown on the Contact page and in the footer.')
                    ->schema([
                        TextInput::make('emails.info')->label('General (info@)')->email()->required(),
                        TextInput::make('emails.sales')->label('Sales (sales@)')->email()->required(),
                        TextInput::make('emails.nutrition')->label('Nutrition (nutrition@)')->email()->required(),
                        TextInput::make('emails.partners')->label('Partners (partners@)')->email()->required(),
                        TextInput::make('emails.noreply')->label('No-reply sender (noreply@)')->email()->required(),
                    ])
                    ->columns(2),
                Section::make('WhatsApp')
                    ->description('The WhatsApp buttons and "Request a Call" appear on the site only when a number is set. Use shared business numbers, not personal ones.')
                    ->schema([
                        TextInput::make('whatsapp_sales')->label('WhatsApp Sales')->tel()->maxLength(30),
                        TextInput::make('whatsapp_nutrition')->label('WhatsApp Nutrition Team')->tel()->maxLength(30),
                    ])
                    ->columns(2),
                Section::make('Company status')
                    ->description('Used on quotations in the CRM (the contracting entity). Never shown on the public website. Tick "Incorporated" only when the company is incorporated.')
                    ->schema([
                        Toggle::make('sg_incorporated')->label('Incorporated in Singapore')->live(),
                        TextInput::make('legal_name')->label('Legal name')->maxLength(200)
                            ->required(fn (Get $get): bool => (bool) $get('sg_incorporated')),
                        TextInput::make('uen')->label('UEN')->maxLength(20),
                        TextInput::make('registered_office')->label('Registered office')->maxLength(300),
                        TextInput::make('pk_partner_name')->label('Pakistan partner: legal name')->maxLength(200),
                        TextInput::make('pk_partner_city')->label('Pakistan partner: city')->maxLength(100),
                        TextInput::make('pk_partner_role')->label('Pakistan partner: role')->maxLength(200),
                    ])
                    ->columns(2),
                Section::make('Enquiries')
                    ->schema([
                        TextInput::make('enquiry_response_time')
                            ->label('Response-time promise')
                            ->helperText('Used in the acknowledgement e-mail: "We aim to reply {this}." Leave empty to make no promise.')
                            ->placeholder('within one working day')
                            ->maxLength(100),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Save settings')->submit('save')])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $settings = app(SiteSettings::class);

        foreach (['whatsapp_sales', 'whatsapp_nutrition', 'legal_name', 'uen', 'registered_office', 'pk_partner_name', 'pk_partner_city', 'pk_partner_role', 'enquiry_response_time'] as $nullable) {
            $data[$nullable] = filled($data[$nullable] ?? null) ? trim((string) $data[$nullable]) : null;
        }

        $settings->fill([...$data, 'sg_incorporated' => (bool) ($data['sg_incorporated'] ?? false)])->save();

        activity('crm')->causedBy(Filament::auth()->user())->event('updated')->log('site settings updated');

        Notification::make()->title('Site settings saved')->success()->send();
    }
}
