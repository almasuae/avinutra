<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Roles;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use LiteCrm\Filament\Resources\CrmResource;
use LiteCrm\LiteCrm;
use LiteCrm\Support\CrmSettings;
use LiteCrm\Support\Permissions;

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
        return $schema->components([
            TextInput::make('name')
                ->label(__('lite-crm::roles.fields.name'))
                ->required()
                ->maxLength(100)
                ->regex('/^[a-z][a-z0-9_]*$/')
                ->unique(ignoreRecord: true)
                ->disabled(fn (?Model $record): bool => static::isSystemRole($record)),
            TextInput::make('guard_name')->default('web')->hidden()->dehydratedWhenHidden(),
            CheckboxList::make('permissions')
                ->label(__('lite-crm::roles.fields.permissions'))
                ->relationship('permissions', 'name')
                ->columns(3)
                ->searchable()
                ->bulkToggleable(),
        ]);
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
            ->recordActions([EditAction::make(), DeleteAction::make()]);
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
