<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use LiteCrm\LiteCrm;
use LiteCrm\Models\Task;

/**
 * Seeds every open row of CONTENT-GAPS.md as a CRM task assigned to the first
 * admin (v5 §A3.3). Safe to run again: a gap's task is found by its number,
 * never duplicated, and assigned once an admin exists (run this again after
 * `lite-crm:create-admin`).
 */
class ContentGapTaskSeeder extends Seeder
{
    public const TITLE_PREFIX = 'Content gap #';

    public function run(): void
    {
        $admin = $this->firstAdmin();

        foreach (self::openGaps(base_path('CONTENT-GAPS.md')) as $gap) {
            /** @var Task|null $task */
            $task = LiteCrm::model(Task::class)::query()
                ->where('title', 'like', self::TITLE_PREFIX.$gap['number'].':%')
                ->first();

            if ($task === null) {
                LiteCrm::model(Task::class)::query()->create([
                    'title' => self::TITLE_PREFIX.$gap['number'].': '.mb_strimwidth($gap['missing'], 0, 180, '…'),
                    'description' => implode("\n", [
                        'Page(s): '.$gap['pages'],
                        'What is missing: '.$gap['missing'],
                        'How the site handles it now: '.$gap['handling'],
                        'Supplied by: '.$gap['supplier'],
                        'When it is filled: update the setting or record in the CRM, check the page, and move the row to "Resolved" in CONTENT-GAPS.md.',
                    ]),
                    'assignee_id' => $admin?->getKey(),
                    'owner_id' => $admin?->getKey(),
                ]);

                continue;
            }

            if ($task->assignee_id === null && $admin !== null) {
                $task->update(['assignee_id' => $admin->getKey(), 'owner_id' => $task->owner_id ?? $admin->getKey()]);
            }
        }
    }

    /**
     * The rows of the "Open" table.
     *
     * @return list<array{number: int, pages: string, missing: string, handling: string, supplier: string}>
     */
    public static function openGaps(string $file): array
    {
        $markdown = (string) @file_get_contents($file);
        $open = preg_match('/^## Open\s*$(.*?)^## /ms', $markdown, $match) === 1 ? $match[1] : '';
        $gaps = [];

        foreach (preg_split('/\R/', $open) ?: [] as $line) {
            if (preg_match('/^\|\s*(\d+)\s*\|(.*)\|\s*$/', $line, $row) !== 1) {
                continue;
            }

            $cells = array_map(fn (string $cell): string => trim(str_replace('`', '', $cell)), explode('|', $row[2]));

            if (count($cells) < 4) {
                continue;
            }

            $gaps[] = ['number' => (int) $row[1], 'pages' => $cells[0], 'missing' => $cells[1], 'handling' => $cells[2], 'supplier' => $cells[3]];
        }

        return $gaps;
    }

    protected function firstAdmin(): ?Model
    {
        return LiteCrm::userModel()::query()
            ->whereHas('roles', fn ($query) => $query->where('name', LiteCrm::superAdminRole()))
            ->orderBy('id')
            ->first();
    }
}
