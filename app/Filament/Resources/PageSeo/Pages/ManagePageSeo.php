<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSeo\Pages;

use App\Filament\Resources\PageSeo\PageSeoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePageSeo extends ManageRecords
{
    protected static string $resource = PageSeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
