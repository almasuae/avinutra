<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

/**
 * Full page (CRM form layout): long text gets the whole width of the screen.
 */
class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
