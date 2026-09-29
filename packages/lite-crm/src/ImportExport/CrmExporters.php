<?php

declare(strict_types=1);

namespace LiteCrm\ImportExport;

use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\ImportAction;
use Filament\Actions\Imports\Importer;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LiteCrm\LiteCrm;
use LiteCrm\Support\Permissions;

/**
 * Import and export actions for list pages, with the CRM's rules:
 * import needs "import.run" and permission to create the records; export
 * needs "{module}.export" (so never for Partners or Viewers), includes only
 * records the user may see, is stored on the private disk and is audit-logged.
 */
class CrmExporters
{
    /**
     * @param  class-string<Importer>  $importer
     * @param  class-string<Model>  $model
     */
    public static function importAction(string $importer, string $model): ImportAction
    {
        return ImportAction::make()
            ->importer($importer)
            ->label(__('lite-crm::import.import'))
            ->visible(fn (): bool => LiteCrm::isModuleEnabled('import_export')
                && Permissions::allows(Filament::auth()->user(), 'import.run')
                && Gate::allows('create', LiteCrm::model($model)))
            ->maxRows(10000);
    }

    /**
     * @param  class-string<Exporter>  $exporter
     */
    public static function exportAction(string $exporter, string $module): ExportAction
    {
        return ExportAction::make()
            ->exporter($exporter)
            ->label(__('lite-crm::import.export'))
            ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
            ->fileDisk((string) config('lite-crm.documents.disk', 'local'))
            ->visible(fn (): bool => LiteCrm::isModuleEnabled('import_export')
                && Permissions::allows(Filament::auth()->user(), $module.'.export'))
            ->after(function () use ($exporter, $module): void {
                activity('crm')
                    ->causedBy(Filament::auth()->user())
                    ->event('exported')
                    ->withProperties(['module' => $module, 'exporter' => class_basename($exporter)])
                    ->log('exported');
            });
    }
}
