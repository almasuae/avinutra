<?php

declare(strict_types=1);

namespace LiteCrm\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a private document. The route requires a valid, unexpired signature
 * and a signed-in user, and the user must be allowed to view the document.
 * Every download is written to the audit log.
 */
class DownloadDocument
{
    public function __invoke(int|string $document): StreamedResponse
    {
        /** @var Document|null $record */
        $record = LiteCrm::model(Document::class)::query()->find($document);

        abort_if($record === null, 404);

        $user = Filament::auth()->user();

        abort_unless($user !== null && Gate::forUser($user)->allows('view', $record), 403);

        $disk = Storage::disk($record->disk);

        abort_unless($disk->exists($record->file_path), 404);

        activity('crm')
            ->causedBy($user)
            ->performedOn($record)
            ->event('downloaded')
            ->log('downloaded');

        return $disk->download($record->file_path, $record->file_name, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
