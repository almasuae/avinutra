<?php

declare(strict_types=1);

namespace LiteCrm\Filament\Resources\Documents;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use LiteCrm\Filament\FormLayout;
use LiteCrm\Filament\Resources\RecordResource;
use LiteCrm\Filament\Support\Fields;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Document;
use LiteCrm\Models\Lookup;

class DocumentResource extends RecordResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'documents';

    public static function getModel(): string
    {
        return LiteCrm::model(Document::class);
    }

    public static function getModelLabel(): string
    {
        return __('lite-crm::documents.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lite-crm::documents.plural');
    }

    public static function warningDays(): int
    {
        return (int) config('lite-crm.notifications.expiry_warning_days', 60);
    }

    public static function form(Schema $schema): Schema
    {
        /** @var list<string> $mimeTypes */
        $mimeTypes = config('lite-crm.documents.accepted_mime_types', []);

        return $schema->columns(2)->components([
            TextInput::make('title')->label(__('lite-crm::documents.fields.title'))->required()->maxLength(255),
            Fields::lookup('type_id', 'document_type', __('lite-crm::documents.fields.type')),
            FileUpload::make('file_path')
                ->label(__('lite-crm::documents.fields.file'))
                ->helperText(__('lite-crm::documents.fields.file_help', ['size' => (int) round((int) config('lite-crm.documents.max_size_kb', 10240) / 1024)]))
                ->disk((string) config('lite-crm.documents.disk', 'local'))
                ->directory((string) config('lite-crm.documents.directory', 'crm/documents'))
                ->visibility('private')
                ->acceptedFileTypes($mimeTypes)
                ->maxSize((int) config('lite-crm.documents.max_size_kb', 10240))
                ->storeFileNamesIn('file_name')
                ->previewable(false)
                ->downloadable(false)
                ->openable(false)
                ->required()
                ->columnSpanFull(),
            Fields::recordSelect('documentable'),
            TextInput::make('issuer')->label(__('lite-crm::documents.fields.issuer'))->maxLength(255),
            TextInput::make('certificate_number')->label(__('lite-crm::documents.fields.certificate_number'))->maxLength(255),
            DatePicker::make('issued_on')->label(__('lite-crm::documents.fields.issued_on')),
            DatePicker::make('expires_on')->label(__('lite-crm::documents.fields.expires_on'))->afterOrEqual('issued_on'),
            Toggle::make('confidential')
                ->label(__('lite-crm::documents.fields.confidential'))
                ->helperText(__('lite-crm::documents.fields.confidential_help')),
            Textarea::make('notes')->label(__('lite-crm::documents.fields.notes'))->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label(__('lite-crm::documents.fields.title'))->searchable()->wrap(),
                TextColumn::make('type.label')->label(__('lite-crm::documents.fields.type'))->badge(),
                Fields::recordColumn('documentable'),
                TextColumn::make('certificate_number')->label(__('lite-crm::documents.fields.certificate_number'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expires_on')
                    ->label(__('lite-crm::documents.fields.expires_on'))
                    ->date()
                    ->sortable()
                    ->color(fn (Document $record): ?string => match (true) {
                        $record->isExpired() => 'danger',
                        $record->expiresWithin(static::warningDays()) => 'warning',
                        default => null,
                    }),
                IconColumn::make('verified')->label(__('lite-crm::documents.fields.verified'))->boolean(),
                IconColumn::make('confidential')->label(__('lite-crm::documents.fields.confidential'))->boolean()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type_id')->label(__('lite-crm::documents.fields.type'))->options(fn (): array => Lookup::options('document_type')),
                TernaryFilter::make('verified')->label(__('lite-crm::documents.fields.verified')),
                Filter::make('expiring')
                    ->label(__('lite-crm::documents.filters.expiring', ['days' => static::warningDays()]))
                    ->query(fn (Builder $query): Builder => $query->scopes(['expiringWithin' => [static::warningDays()]])),
                Filter::make('expired')
                    ->label(__('lite-crm::documents.filters.expired'))
                    ->query(fn (Builder $query): Builder => $query->whereDate('expires_on', '<', Date::today())),
                TrashedFilter::make(),
            ])
            ->recordActions([
                static::downloadAction(),
                static::verifyAction(),
                FormLayout::wide(EditAction::make()),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function downloadAction(): Action
    {
        return Action::make('download')
            ->label(__('lite-crm::documents.actions.download'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->visible(fn (Document $record): bool => ! $record->trashed() && Gate::allows('view', $record))
            ->url(fn (Document $record): string => $record->temporaryDownloadUrl())
            ->openUrlInNewTab();
    }

    public static function verifyAction(): Action
    {
        return Action::make('verify')
            ->label(__('lite-crm::documents.actions.verify'))
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('success')
            ->visible(fn (Document $record): bool => ! $record->verified && Gate::allows('update', $record))
            ->schema([
                Textarea::make('verification_method')
                    ->label(__('lite-crm::documents.fields.verification_method'))
                    ->helperText(__('lite-crm::documents.fields.verification_method_help'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Document $record, array $data): void {
                /** @var Model $user */
                $user = Filament::auth()->user();
                $record->markVerified($user, (string) $data['verification_method']);

                Notification::make()->title(__('lite-crm::documents.notifications.verified'))->success()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDocuments::route('/'),
        ];
    }
}
