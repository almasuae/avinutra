<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Data fix (owner's review, 30 Sep 2026): a seeded article source ended with the note
 * "(title shortened; product name omitted)". The public title now uses "…" where it is
 * shortened, and the reason moves to the source's internal note (never shown publicly).
 * Only this exact seeded title is changed; sources edited in the CRM are left alone.
 */
return new class extends Migration
{
    private const OLD = 'EFSA FEEDAP Panel (2018). Safety and efficacy of hydroxy analogue of methionine and its calcium salt for all animal species (title shortened; product name omitted). EFSA Journal 16(3):5198';

    private const NEW = 'EFSA FEEDAP Panel (2018). Safety and efficacy of hydroxy analogue of methionine and its calcium salt … for all animal species. EFSA Journal 16(3):5198';

    private const NOTE = 'Title shortened: the product (trade) name in the original title is left out, because the site names no trademarks.';

    public function up(): void
    {
        DB::table('articles')->whereNotNull('sources')->orderBy('id')->each(function (object $article): void {
            $sources = json_decode((string) $article->sources, true);

            if (! is_array($sources)) {
                return;
            }

            $changed = false;

            foreach ($sources as $i => $source) {
                if (is_array($source) && ($source['title'] ?? null) === self::OLD) {
                    $sources[$i]['title'] = self::NEW;
                    $sources[$i]['note'] ??= self::NOTE;
                    $changed = true;
                }
            }

            if ($changed) {
                DB::table('articles')->where('id', $article->id)->update(['sources' => json_encode($sources, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        });
    }

    public function down(): void
    {
        // Content fix only; nothing to undo.
    }
};
