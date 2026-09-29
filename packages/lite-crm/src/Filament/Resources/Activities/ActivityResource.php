<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Activities;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Activity;
use LiteCrm\Models\Contact;
use LiteCrm\Models\Lookup;
use LiteCrm\Support\Visibility;

class ActivityResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'activities';

    public static function getModel(): string
    {
        return LiteCrm::model(Activity::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::activities.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::activities.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $user = fn (): mixed => Filament::auth()->user();

        return $schema->columns(2)->components([
            Fields::recordSelect('subject'),
            Fields::lookup('type_id', 'activity_type', __('lite-crm::activities.fields.type'))->required(),
            DateTimePicker::make('occurred_at')
                ->label(__('lite-crm::activities.fields.occurred_at'))
                ->seconds(false)
                ->default(fn (): mixed => now())
                ->required(),
            TextInput::make('duration_minutes')
                ->label(__('lite-crm::activities.fields.duration'))
                ->integer()
                ->minValue(0)
                ->suffix(__('lite-crm::activities.minutes')),
            Select::make('participantUsers')
                ->label(__('lite-crm::activities.fields.participant_users'))
                ->relationship('participantUsers', 'name', fn (Builder $query) => $query->whereHas('crmProfile', fn (Builder $profile) => $profile->where('is_active', true)))
                ->multiple()
                ->preload(),
            Select::make('participantContacts')
                ->label(__('lite-crm::activities.fields.participant_contacts'))
                ->relationship('participantContacts', 'last_name', fn (Builder $query) => Visibility::apply($query, $user()))
                ->getOptionLabelFromRecordUsing(fn (Contact $record): string => $record->name)
                ->searchable(['first_name', 'last_name'])
                ->multiple(),
            Textarea::make('summary')->label(__('lite-crm::activities.fields.summary'))->required()->columnSpanFull(),
            Textarea::make('outcome')->label(__('lite-crm::activities.fields.outcome'))->columnSpanFull(),
            TextInput::make('next_step')->label(__('lite-crm::activities.fields.next_step'))->maxLength(255)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->label(__('lite-crm::activities.fields.occurred_at'))->dateTime()->sortable(),
                TextColumn::make('type.label')->label(__('lite-crm::activities.fields.type'))->badge(),
                Fields::recordColumn('subject'),
                TextColumn::make('summary')->label(__('lite-crm::activities.fields.summary'))->limit(80)->wrap()->searchable(),
                TextColumn::make('next_step')->label(__('lite-crm::activities.fields.next_step'))->limit(40)->toggleable(),
                TextColumn::make('owner.name')->label(__('lite-crm::activities.fields.logged_by'))->toggleable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('type_id')->label(__('lite-crm::activities.fields.type'))->options(fn (): array => Lookup::options('activity_type')),
                SelectFilter::make('owner_id')->label(__('lite-crm::activities.fields.logged_by'))->options(fn (): array => LiteCrm::userOptions()),
                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('from')->label(__('lite-crm::common.filters.from')),
                        DatePicker::make('until')->label(__('lite-crm::common.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([FormLayout::wide(EditAction::make()), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivities::route('/'),
        ];
    }
}
