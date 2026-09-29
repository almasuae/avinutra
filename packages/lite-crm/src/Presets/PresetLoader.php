<?php

declare(strict_types=1);

namespace LiteCrm\Presets;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LiteCrm\LiteCrm;
use LiteCrm\LiteCrmServiceProvider;
use LiteCrm\Models\CustomField;
use LiteCrm\Models\Lookup;
use LiteCrm\Models\Opportunity;
use LiteCrm\Models\Pipeline;
use LiteCrm\Support\CrmSettings;

/**
 * Loads an industry preset: lists, pipelines with stages, custom fields and
 * settings. Idempotent — running it again adds only what is missing — and it
 * never overrides what an Admin changed:
 *
 * - list entries and pipelines are created if missing (a deleted entry is
 *   restored); lists marked "replace" deactivate the other entries (never delete);
 * - pipelines marked "replace" deactivate other pipelines that hold no
 *   opportunities; missing stages are added to existing pipelines;
 * - custom fields are created if missing;
 * - currencies are merged; base currency and quotation prefix are set only if
 *   unset; role labels are added only for roles without one.
 */
class PresetLoader
{
    /**
     * Presets available by name (package presets/, plus lite-crm.preset_paths).
     *
     * @return array<string, string> name => file
     */
    public function available(): array
    {
        /** @var list<string> $paths */
        $paths = [LiteCrmServiceProvider::packagePath('presets'), ...(array) config('lite-crm.preset_paths', [])];
        $presets = [];

        foreach ($paths as $path) {
            foreach (glob(rtrim($path, '/\\').DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
                $presets[basename($file, '.php')] = $file;
            }
        }

        ksort($presets);

        return $presets;
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $name): array
    {
        $file = $this->available()[$name] ?? null;

        if ($file === null) {
            throw new InvalidArgumentException(__('lite-crm::presets.not_found', ['name' => $name]));
        }

        $preset = require $file;

        if (! is_array($preset)) {
            throw new InvalidArgumentException(__('lite-crm::presets.invalid', ['name' => $name]));
        }

        return $preset;
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return array{lookups: int, pipelines: int, stages: int, custom_fields: int, deactivated: int}
     */
    public function apply(array $preset): array
    {
        $summary = ['lookups' => 0, 'pipelines' => 0, 'stages' => 0, 'custom_fields' => 0, 'deactivated' => 0];

        DB::transaction(function () use ($preset, &$summary): void {
            $this->applyLookups((array) ($preset['lookups'] ?? []), $summary);
            $this->applyPipelines((array) ($preset['pipelines'] ?? []), $summary);
            $this->applyCustomFields((array) ($preset['custom_fields'] ?? []), $summary);
            $this->applySettings((array) ($preset['settings'] ?? []));
        });

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $lookups
     * @param  array<string, int>  $summary
     */
    protected function applyLookups(array $lookups, array &$summary): void
    {
        /** @var class-string<Lookup> $model */
        $model = LiteCrm::model(Lookup::class);

        foreach ($lookups as $type => $definition) {
            /** @var array<string, string> $items */
            $items = (array) ($definition['items'] ?? []);
            $sort = 0;

            foreach ($items as $key => $label) {
                $sort += 10;
                $lookup = $model::withTrashed()->firstOrNew(['type' => $type, 'key' => (string) $key]);

                if (! $lookup->exists) {
                    $lookup->fill(['label' => $label, 'sort' => $sort, 'is_active' => true])->save();
                    $summary['lookups']++;

                    continue;
                }

                if ($lookup->trashed()) {
                    $lookup->restore();
                }

                if (! $lookup->is_active) {
                    $lookup->update(['is_active' => true]);
                }
            }

            if (($definition['replace'] ?? false) === true) {
                $summary['deactivated'] += $model::query()
                    ->where('type', $type)
                    ->whereNotIn('key', array_map('strval', array_keys($items)))
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pipelines
     * @param  array<string, int>  $summary
     */
    protected function applyPipelines(array $pipelines, array &$summary): void
    {
        /** @var class-string<Pipeline> $model */
        $model = LiteCrm::model(Pipeline::class);
        /** @var array<string, array<string, mixed>> $items */
        $items = (array) ($pipelines['items'] ?? []);
        $sort = 0;

        foreach ($items as $key => $definition) {
            $sort += 10;
            $pipeline = $model::withTrashed()->firstOrNew(['key' => $key]);

            if (! $pipeline->exists) {
                $pipeline->fill([
                    'name' => $definition['name'],
                    'sort' => $sort,
                    'is_active' => true,
                    'visible_to_roles' => $definition['visible_to_roles'] ?? null,
                ])->save();
                $summary['pipelines']++;
            } elseif ($pipeline->trashed()) {
                $pipeline->restore();
            }

            $stageSort = 0;

            /** @var array<string, array{0: string, 1?: int, 2?: string}> $stages */
            $stages = (array) ($definition['stages'] ?? []);

            foreach ($stages as $stageKey => $stage) {
                $stageSort += 10;

                if ($pipeline->stages()->where('key', $stageKey)->exists()) {
                    continue;
                }

                $pipeline->stages()->create([
                    'key' => $stageKey,
                    'name' => $stage[0],
                    'probability' => $stage[1] ?? 0,
                    'is_won' => ($stage[2] ?? null) === 'won',
                    'is_lost' => ($stage[2] ?? null) === 'lost',
                    'sort' => $stageSort,
                ]);
                $summary['stages']++;
            }
        }

        if (($pipelines['replace'] ?? false) === true) {
            $others = $model::query()->whereNotIn('key', array_keys($items))->where('is_active', true)->get();

            foreach ($others as $other) {
                if (! LiteCrm::model(Opportunity::class)::withTrashed()->where('pipeline_id', $other->getKey())->exists()) {
                    $other->update(['is_active' => false]);
                    $summary['deactivated']++;
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, int>  $summary
     */
    protected function applyCustomFields(array $fields, array &$summary): void
    {
        /** @var class-string<CustomField> $model */
        $model = LiteCrm::model(CustomField::class);

        foreach ($fields as $sort => $field) {
            $existing = $model::withTrashed()->where('entity', $field['entity'])->where('key', $field['key'])->first();

            if ($existing !== null) {
                continue;
            }

            $model::query()->create([
                'sort' => ($sort + 1) * 10,
                ...$field,
                'options' => isset($field['options'])
                    ? array_map(fn ($value, $label): array => ['value' => (string) $value, 'label' => (string) $label], array_keys($field['options']), $field['options'])
                    : null,
            ]);
            $summary['custom_fields']++;
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function applySettings(array $settings): void
    {
        $crm = app(CrmSettings::class);

        if (isset($settings['currencies'])) {
            $current = (array) $crm->get(CrmSettings::CURRENCIES, []);
            $crm->set(CrmSettings::CURRENCIES, array_values(array_unique([...$current, ...(array) $settings['currencies']])));
        }

        foreach (['base_currency' => CrmSettings::BASE_CURRENCY, 'quotation_prefix' => CrmSettings::QUOTATION_PREFIX] as $key => $setting) {
            if (isset($settings[$key]) && blank($crm->get($setting))) {
                $crm->set($setting, $settings[$key]);
            }
        }

        if (isset($settings['role_labels'])) {
            /** @var array<string, string> $labels */
            $labels = (array) $crm->get(CrmSettings::ROLE_LABELS, []);
            $crm->set(CrmSettings::ROLE_LABELS, $labels + (array) $settings['role_labels']);
        }
    }
}
