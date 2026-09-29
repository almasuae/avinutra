<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamProfiles;

use App\Enums\TeamRole;
use App\Filament\Resources\WebsiteResource;
use App\Models\TeamProfile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Team profiles (v3 §7.2): shown on the site only when published AND written,
 * dated consent is on file. The Team page appears once one profile qualifies.
 */
class TeamProfileResource extends WebsiteResource
{
    protected static ?string $model = TeamProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->schema([
                    TextInput::make('name')->required()->maxLength(150),
                    Select::make('role_type')->label('Works with us as')->options(TeamRole::class)->required()
                        ->helperText('Advisers are never presented as employees.'),
                    TextInput::make('job_title')->maxLength(150),
                    TextInput::make('languages')->maxLength(200),
                    TextInput::make('qualification')->label('Highest qualification')->maxLength(200),
                    TextInput::make('university')->maxLength(200),
                    TextInput::make('qualification_year')->label('Year')->numeric()->minValue(1950)->maxValue((int) date('Y')),
                    TextInput::make('experience_years')->label('Years of poultry/feed experience')->numeric()->minValue(0)->maxValue(70),
                    Textarea::make('specialisations')->rows(2)->columnSpanFull(),
                    Textarea::make('bio')->rows(4)->maxLength(2000)->columnSpanFull(),
                    FileUpload::make('photo_path')->label('Photograph')->image()->disk('public')->directory('team')->visibility('public')->maxSize(2048)->columnSpanFull(),
                    Repeater::make('publications')->label('Publications and memberships')
                        ->schema([TextInput::make('title')->required()->maxLength(300), TextInput::make('url')->url()->maxLength(500)])
                        ->columns(2)->defaultItems(0)->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Consent and publication')
                ->description('Every credential must be verifiable. Publish only with written consent for the name, photograph and biography.')
                ->schema([
                    Toggle::make('consent_on_file')->label('Written consent on file')->live(),
                    DatePicker::make('consent_date')->label('Consent date')->required(fn (Get $get): bool => (bool) $get('consent_on_file')),
                    Toggle::make('is_published')->label('Published on the website')
                        ->disabled(fn (Get $get): bool => ! $get('consent_on_file'))
                        ->helperText('Available once written consent is on file.'),
                    TextInput::make('sort')->numeric()->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('role_type')->label('Role')->badge(),
                IconColumn::make('consent_on_file')->label('Consent')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->defaultSort('sort')
            ->recordActions([EditAction::make()->slideOver(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeamProfiles::route('/')];
    }
}
