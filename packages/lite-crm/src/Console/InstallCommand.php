<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use LiteCrm\Database\Seeders\LiteCrmSeeder;

class InstallCommand extends Command
{
    protected $signature = 'lite-crm:install
        {--force : Overwrite a published config file and run migrations in production without asking}';

    protected $description = 'Publish the config, run the migrations and seed roles, permissions and neutral lookups';

    public function handle(): int
    {
        $this->components->info(__('lite-crm::console.install.start'));

        if (! file_exists(config_path('lite-crm.php')) || $this->option('force')) {
            $this->callSilently('vendor:publish', ['--tag' => 'lite-crm-config', '--force' => (bool) $this->option('force')]);
            $this->components->task(__('lite-crm::console.install.config_published'));
        } else {
            $this->components->twoColumnDetail(__('lite-crm::console.install.config_kept'), 'config/lite-crm.php');
        }

        $this->call('migrate', ['--force' => true]);

        $this->call('db:seed', ['--class' => LiteCrmSeeder::class, '--force' => true]);

        $this->newLine();
        $this->components->info(__('lite-crm::console.install.done'));
        $this->components->bulletList([
            __('lite-crm::console.install.next.user_model'),
            __('lite-crm::console.install.next.panel'),
            __('lite-crm::console.install.next.admin'),
            __('lite-crm::console.install.next.cron'),
            __('lite-crm::console.install.next.mail'),
        ]);

        return self::SUCCESS;
    }
}
