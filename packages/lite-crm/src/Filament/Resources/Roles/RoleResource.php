<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Roles;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\Permissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and their permissions. The six default roles cannot be renamed or
 * deleted; their labels are set in CRM › Settings.
 */
class RoleResource extends CrmResource
{
    protected static ?string $viewPermission = 'roles.manage';

    protected static ?string $managePermission = 'roles.manage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'crm-roles';

    public static function getModel(): string
    {
        return LiteCrm::roleModel();
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::roles.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::roles.plural');
    }

    public static function isSystemRole(?Model $record): bool
    {
        return $record !== null && in_array($record->getAttribute('name'), Permissions::ROLES, true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            // A compact top row: the name takes about a third of the width.
            Grid::make(['default' => 1, 'md' => 3])->schema([
                TextInput::make('name')
                    ->label(__('lite-crm::roles.fields.name'))
                    ->required()
                    ->maxLength(100)
                    ->regex('/^[a-z][a-z0-9_]*$/')
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (?Model $record): bool => static::isSystemRole($record)),
            ]),
            TextInput::make('guard_name')->default('web')->hidden()->dehydratedWhenHidden(),
            ViewField::make('permissions')
                ->label(__('lite-crm::roles.fields.permissions'))
                ->view('lite-crm::filament.roles.permission-picker')
                ->columnSpanFull()
                ->default([])
                ->afterStateHydrated(function (ViewField $component, ?Model $record): void {
                    $component->state($record instanceof Role ? $record->permissions()->pluck('name')->all() : []);
                })
                ->rule('array')
                ->dehydrated(false)
                ->saveRelationshipsUsing(function (Model $record, mixed $state): void {
                    if (! $record instanceof Role) {
                        return;
                    }

                    // Stored names never change; anything not in the permission table is ignored.
                    $names = array_values(array_intersect(is_array($state) ? $state : [], array_keys(static::permissionNames())));
                    $record->syncPermissions($names);
                }),
        ]);
    }

    /** Modal width of the create and edit forms: the permission grid needs the room. */
    public static function formWidth(): Width
    {
        return Width::SevenExtraLarge;
    }

    /**
     * Every stored permission, name => readable label.
     *
     * @return array<string, string>
     */
    public static function permissionNames(): array
    {
        $names = app(PermissionRegistrar::class)->getPermissionClass()::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return collect($names)->mapWithKeys(fn (string $name): array => [$name => Permissions::label($name)])->all();
    }

    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function permissionGroups(): array
    {
        return Permissions::grouped(array_keys(static::permissionNames()));
    }

    public static function table(Table $table): Table
    {
        $settings = app(CrmSettings::class);

        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('lite-crm::roles.fields.label'))
                    ->state(fn (Model $record): string => $settings->roleLabel((string) $record->getAttribute('name'))),
                TextColumn::make('name')->label(__('lite-crm::roles.fields.name'))->searchable(),
                TextColumn::make('permissions_count')->label(__('lite-crm::roles.fields.permissions'))->counts('permissions'),
                TextColumn::make('users_count')->label(__('lite-crm::roles.fields.users'))->counts('users'),
            ])
            ->defaultSort('id')
            ->recordActions([EditAction::make()->modalWidth(static::formWidth()), DeleteAction::make()]);
    }

    protected static function canBeDeleted(?Model $record): bool
    {
        return ! static::isSystemRole($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
