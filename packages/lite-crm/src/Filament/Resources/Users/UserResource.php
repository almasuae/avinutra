<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Users;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Actions\InviteUser;
use LiteCrm\Filament\Pages\EditProfile;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Models\UserProfile;
use LiteCrm\Support\CrmSettings;

/**
 * Team members. "Create" sends an invitation; users are deactivated, never deleted.
 */
class UserResource extends CrmResource
{
    protected static ?string $viewPermission = 'users.view';

    protected static ?string $managePermission = 'users.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'crm-users';

    public static function getModel(): string
    {
        return LiteCrm::userModel();
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::users.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::users.plural');
    }

    /**
     * Only users who belong to the CRM (have a CRM profile).
     *
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('crmProfile')->with(['crmProfile', 'roles']);
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        $settings = app(CrmSettings::class);
        $options = [];

        foreach (LiteCrm::roleModel()::query()->orderBy('id')->pluck('name') as $name) {
            $options[(string) $name] = $settings->roleLabel((string) $name);
        }

        return $options;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label(__('lite-crm::users.fields.name'))->required()->maxLength(255),
            TextInput::make('email')
                ->label(__('lite-crm::users.fields.email'))
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(table: fn (): string => (new (LiteCrm::userModel()))->getTable(), ignoreRecord: true),
            CheckboxList::make('roles')
                ->label(__('lite-crm::users.fields.roles'))
                ->options(static::roleOptions())
                ->required()
                ->columns(3)
                ->columnSpanFull(),
            Section::make(__('lite-crm::users.sections.profile'))
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ...EditProfile::profileFormComponents(),
                    Select::make('profile.territory_id')
                        ->label(__('lite-crm::users.fields.territory'))
                        ->options(fn (): array => EditProfile::territoryOptions()),
                    Toggle::make('profile.is_active')
                        ->label(__('lite-crm::users.fields.is_active'))
                        ->default(true)
                        ->hiddenOn('create')
                        ->disabled(fn (?Model $record): bool => $record !== null && $record->is(Filament::auth()->user())),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $settings = app(CrmSettings::class);

        return $table
            ->columns([
                TextColumn::make('name')->label(__('lite-crm::users.fields.name'))->searchable()->sortable(),
                TextColumn::make('email')->label(__('lite-crm::users.fields.email'))->searchable()->toggleable(),
                TextColumn::make('roles.name')
                    ->label(__('lite-crm::users.fields.roles'))
                    ->formatStateUsing(fn (string $state): string => $settings->roleLabel($state))
                    ->badge(),
                TextColumn::make('crmProfile.city')->label(__('lite-crm::users.fields.city'))->toggleable(),
                TextColumn::make('crmProfile.time_zone')->label(__('lite-crm::users.fields.time_zone'))->toggleable(),
                TextColumn::make('status')
                    ->label(__('lite-crm::users.fields.status'))
                    ->state(fn (Model $record): string => static::status($record))
                    ->formatStateUsing(fn (string $state): string => __("lite-crm::users.status.{$state}"))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'invited' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('crmProfile.last_login_at')
                    ->label(__('lite-crm::users.fields.last_login_at'))
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('lite-crm::users.fields.roles'))
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Model $record): string => $settings->roleLabel((string) $record->getAttribute('name'))),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, Model $record): array => static::fillFormData($data, $record))
                    ->using(fn (Model $record, array $data): Model => static::updateUser($record, $data)),
                Action::make('resendInvitation')
                    ->label(__('lite-crm::users.actions.resend_invitation'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => static::canEdit($record) && static::status($record) === 'invited')
                    ->action(function (Model $record): void {
                        /** @var Model|null $inviter */
                        $inviter = Filament::auth()->user();
                        app(InviteUser::class)->send(EditProfile::crmUser($record), $inviter);

                        Notification::make()->title(__('lite-crm::users.notifications.invitation_sent'))->success()->send();
                    }),
            ]);
    }

    public static function status(Model $user): string
    {
        /** @var UserProfile|null $profile */
        $profile = $user->getRelationValue('crmProfile');

        return match (true) {
            $profile === null, ! $profile->is_active => 'inactive',
            $profile->isInvitationPending() => 'invited',
            default => 'active',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fillFormData(array $data, Model $record): array
    {
        $user = EditProfile::crmUser($record);
        $profile = $user->getCrmProfile();

        $data['roles'] = $user->roles()->pluck('name')->all();
        $data['profile'] = $profile->only([...EditProfile::PROFILE_FIELDS, 'territory_id', 'is_active']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function updateUser(Model $record, array $data): Model
    {
        $record = EditProfile::crmUser($record);
        $record->forceFill(['name' => $data['name'], 'email' => $data['email']])->save();

        $profile = $record->getCrmProfile();
        /** @var array<string, mixed> $profileData */
        $profileData = $data['profile'] ?? [];

        // Nobody can deactivate themselves.
        if ($record->is(Filament::auth()->user())) {
            unset($profileData['is_active']);
        }

        $profile->fill(array_intersect_key($profileData, array_flip([...EditProfile::PROFILE_FIELDS, 'territory_id', 'is_active'])));
        $profile->save();

        /** @var list<string> $roles */
        $roles = $data['roles'] ?? [];

        // Nobody can remove the admin role from themselves.
        if ($record->is(Filament::auth()->user()) && $record->hasRole(LiteCrm::superAdminRole())) {
            $roles = array_values(array_unique([...$roles, LiteCrm::superAdminRole()]));
        }

        $before = $record->roles()->pluck('name')->sort()->values()->all();
        $record->syncRoles($roles);
        $after = collect($roles)->sort()->values()->all();

        if ($before !== $after) {
            activity('crm')
                ->causedBy(Filament::auth()->user())
                ->performedOn($record)
                ->event('roles_changed')
                ->withProperties(['old' => ['roles' => $before], 'attributes' => ['roles' => $after]])
                ->log('roles_changed');
        }

        return $record;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
