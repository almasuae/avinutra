<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LiteCrm\Presets\PresetLoader;

class PresetCommand extends Command
{
    protected $signature = 'lite-crm:preset
        {name? : The preset to load, e.g. a file name in presets/ without ".php"}
        {--list : List the available presets}';

    protected $description = 'Load an industry preset (lists, pipelines, custom fields, settings); safe to run again';

    public function handle(PresetLoader $loader): int
    {
        $name = $this->argument('name');

        if ($this->option('list') || ! is_string($name) || $name === '') {
            $this->components->info(__('lite-crm::presets.available'));
            $this->components->bulletList(array_keys($loader->available()));

            return self::SUCCESS;
        }

        try {
            $preset = $loader->load($name);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $loader->apply($preset);

        $this->components->info(__('lite-crm::presets.applied', ['name' => (string) ($preset['name'] ?? $name)]));

        foreach ($summary as $key => $count) {
            $this->components->twoColumnDetail(__("lite-crm::presets.summary.{$key}"), (string) $count);
        }

        return self::SUCCESS;
    }
}
