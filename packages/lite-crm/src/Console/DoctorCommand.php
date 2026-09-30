<?php

declare(strict_types=1);

namespace LiteCrm\Console;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LiteCrm\Enquiries\ApiToken;
use LiteCrm\LiteCrm;
use LiteCrm\Support\CrmSettings;
use Throwable;

/**
 * Checks that an installation is healthy: keys and debug mode, migrations,
 * roles and an admin with MFA, cron (scheduler heartbeat) and the queue, mail,
 * the private documents disk, and the enquiry endpoint. Exits with 1 when a
 * check fails, so it can gate a deployment.
 */
class DoctorCommand extends Command
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    /** Used by the queue worker (Illuminate\Queue\Worker) for timeouts and signals. */
    public const PCNTL_FUNCTIONS = ['pcntl_signal', 'pcntl_async_signals', 'pcntl_alarm'];

    protected $signature = 'lite-crm:doctor';

    protected $description = 'Check the CRM installation: cron, queue, mail, private disk, security';

    /** @var list<array{0: string, 1: string, 2: string}> */
    protected array $results = [];

    public function handle(): int
    {
        $this->results = [];
        $production = app()->environment('production');

        $this->check(__('lite-crm::doctor.app_key'), fn () => filled(config('app.key')) ? [self::OK, ''] : [self::FAIL, __('lite-crm::doctor.app_key_missing')]);
        $this->check(__('lite-crm::doctor.debug'), fn () => $production && config('app.debug') ? [self::FAIL, __('lite-crm::doctor.debug_on')] : [self::OK, $production ? '' : __('lite-crm::doctor.not_production')]);
        $this->check(__('lite-crm::doctor.https'), fn () => ! $production || str_starts_with((string) config('app.url'), 'https://') ? [self::OK, (string) config('app.url')] : [self::FAIL, __('lite-crm::doctor.https_missing')]);
        $this->check(__('lite-crm::doctor.migrations'), fn () => $this->migrations());
        $this->check(__('lite-crm::doctor.roles'), fn () => LiteCrm::roleModel()::query()->where('name', LiteCrm::superAdminRole())->exists() ? [self::OK, ''] : [self::FAIL, __('lite-crm::doctor.roles_missing')]);
        $this->check(__('lite-crm::doctor.admin'), fn () => $this->admins());
        $this->check(__('lite-crm::doctor.scheduler'), fn () => $this->scheduler($production));
        $this->check(__('lite-crm::doctor.scheduler_locks'), fn () => $this->schedulerLocks());
        $this->check(__('lite-crm::doctor.queue'), fn () => $this->queue($production));
        $this->check(__('lite-crm::doctor.pcntl'), fn () => $this->pcntl());
        $this->check(__('lite-crm::doctor.mail'), fn () => $this->mail($production));
        $this->check(__('lite-crm::doctor.private_disk'), fn () => $this->privateDisk());
        $this->check(__('lite-crm::doctor.enquiry_api'), fn () => $this->enquiryApi());

        $this->table(
            [__('lite-crm::doctor.check'), __('lite-crm::doctor.result'), __('lite-crm::doctor.details')],
            array_map(fn (array $row): array => [$row[0], strtoupper($row[1]), $row[2]], $this->results),
        );

        $failed = count(array_filter($this->results, fn (array $row): bool => $row[1] === self::FAIL));

        if ($failed > 0) {
            $this->components->error(__('lite-crm::doctor.failed', ['count' => $failed]));

            return self::FAILURE;
        }

        $this->components->info(__('lite-crm::doctor.passed'));

        return self::SUCCESS;
    }

    /**
     * @param  callable(): array{0: string, 1: string}  $check
     */
    protected function check(string $name, callable $check): void
    {
        try {
            [$status, $details] = $check();
        } catch (Throwable $exception) {
            [$status, $details] = [self::FAIL, Str::limit($exception->getMessage(), 120)];
        }

        $this->results[] = [$name, $status, $details];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function migrations(): array
    {
        /** @var Migrator $migrator */
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            return [self::FAIL, __('lite-crm::doctor.migrations_missing')];
        }

        $files = $migrator->getMigrationFiles([...$migrator->paths(), database_path('migrations')]);
        $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());

        return $pending === [] ? [self::OK, ''] : [self::FAIL, __('lite-crm::doctor.migrations_pending', ['count' => count($pending)])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function admins(): array
    {
        $admins = LiteCrm::userModel()::query()
            ->whereHas('roles', fn ($query) => $query->where('name', LiteCrm::superAdminRole()))
            ->get();

        if ($admins->isEmpty()) {
            return [self::FAIL, __('lite-crm::doctor.no_admin')];
        }

        $withMfa = $admins->filter(fn ($admin): bool => filled($admin->getCrmProfile()->app_authentication_secret));

        return $withMfa->isEmpty()
            ? [self::WARN, __('lite-crm::doctor.admin_without_mfa')]
            : [self::OK, __('lite-crm::doctor.admins', ['count' => $admins->count()])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function scheduler(bool $production): array
    {
        $heartbeat = app(CrmSettings::class)->get(CrmSettings::SCHEDULER_HEARTBEAT);

        if (! is_string($heartbeat)) {
            return [$production ? self::FAIL : self::WARN, __('lite-crm::doctor.scheduler_never')];
        }

        $minutes = (int) Date::parse($heartbeat)->diffInMinutes(Date::now(), true);

        return $minutes <= 5
            ? [self::OK, __('lite-crm::doctor.scheduler_seen', ['minutes' => $minutes])]
            : [$production ? self::FAIL : self::WARN, __('lite-crm::doctor.scheduler_stale', ['minutes' => $minutes])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function queue(bool $production): array
    {
        $connection = (string) config('queue.default');

        if ($connection === 'sync') {
            return [$production ? self::WARN : self::OK, __('lite-crm::doctor.queue_sync')];
        }

        $details = [__('lite-crm::doctor.queue_connection', ['connection' => $connection])];
        $status = self::OK;

        if ($connection === 'database' && Schema::hasTable((string) config('queue.connections.database.table', 'jobs'))) {
            $oldest = DB::table((string) config('queue.connections.database.table', 'jobs'))->min('created_at');

            if ($oldest !== null && Date::now()->getTimestamp() - (int) $oldest > 600) {
                $status = self::WARN;
                $details[] = __('lite-crm::doctor.queue_backlog');
            }
        }

        if (Schema::hasTable('failed_jobs') && ($failed = DB::table('failed_jobs')->count()) > 0) {
            $status = self::WARN;
            $details[] = __('lite-crm::doctor.failed_jobs', ['count' => $failed]);
        }

        return [$status, implode('; ', $details)];
    }

    /**
     * The queue worker needs these pcntl functions for its timeouts and signals. When the
     * extension is loaded but they are disabled for command-line PHP (disable_functions,
     * as on a default HestiaCP server), the worker crashes at once, without any error
     * in the application log, and jobs pile up.
     *
     * @return array{0: string, 1: string}
     */
    protected function pcntl(): array
    {
        if ((string) config('queue.default') === 'sync') {
            return [self::OK, __('lite-crm::doctor.queue_sync')];
        }

        return static::pcntlStatus(extension_loaded('pcntl'), (string) ini_get('disable_functions'), php_ini_loaded_file() ?: 'php.ini');
    }

    /**
     * @param  (callable(string): bool)|null  $functionExists
     * @return array{0: string, 1: string}
     */
    public static function pcntlStatus(bool $extensionLoaded, string $disableFunctions, string $ini, ?callable $functionExists = null): array
    {
        if (! $extensionLoaded) {
            return [self::WARN, __('lite-crm::doctor.pcntl_missing')];
        }

        $functionExists ??= 'function_exists';
        $disabled = array_map(trim(...), explode(',', $disableFunctions));
        $missing = array_values(array_filter(
            self::PCNTL_FUNCTIONS,
            fn (string $function): bool => in_array($function, $disabled, true) || ! $functionExists($function),
        ));

        return $missing === []
            ? [self::OK, implode(', ', self::PCNTL_FUNCTIONS)]
            : [self::FAIL, __('lite-crm::doctor.pcntl_disabled', ['functions' => implode(', ', $missing), 'ini' => $ini])];
    }

    /**
     * Scheduled commands that run "without overlapping" hold a lock while they run. If the
     * process dies, the lock stays until it expires (24 hours by default) and the command
     * silently stops running. Reports locks held far longer than their command should run.
     *
     * @return array{0: string, 1: string}
     */
    protected function schedulerLocks(): array
    {
        $store = (string) config('cache.default');
        $config = (array) config("cache.stores.{$store}", []);

        if (($config['driver'] ?? null) !== 'database') {
            return [self::OK, __('lite-crm::doctor.locks_not_checked', ['store' => $store])];
        }

        $table = (string) ($config['lock_table'] ?? null ?: 'cache_locks');
        $connection = DB::connection($config['lock_connection'] ?? $config['connection'] ?? null);

        if (! $connection->getSchemaBuilder()->hasTable($table)) {
            return [self::OK, ''];
        }

        $prefix = (string) ($config['prefix'] ?? config('cache.prefix'));
        $now = Date::now()->getTimestamp();
        $stuck = [];

        foreach (app(Schedule::class)->events() as $event) {
            if (! $event->withoutOverlapping) {
                continue;
            }

            $expiration = $connection->table($table)->where('key', $prefix.$event->mutexName())->value('expiration');

            if ($expiration === null || (int) $expiration <= $now) {
                continue;
            }

            // Held since = expiry - lifetime. Every-minute commands (the queue worker) should
            // finish within a minute; anything else within an hour.
            $heldMinutes = intdiv($now - ((int) $expiration - $event->expiresAt * 60), 60);
            $limit = $event->expression === '* * * * *' ? 15 : 60;

            if ($heldMinutes > $limit) {
                $stuck[] = Str::limit((string) ($event->command ?? $event->description ?? $event->mutexName()), 60).' ('.$heldMinutes.' min)';
            }
        }

        return $stuck === []
            ? [self::OK, '']
            : [self::FAIL, __('lite-crm::doctor.locks_stuck', ['commands' => implode('; ', $stuck)])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function mail(bool $production): array
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');

        if ($production && in_array($mailer, ['log', 'array'], true)) {
            return [self::FAIL, __('lite-crm::doctor.mail_not_sent', ['mailer' => $mailer])];
        }

        if ($from === '' || str_ends_with($from, '@example.com')) {
            return [$production ? self::FAIL : self::WARN, __('lite-crm::doctor.mail_from')];
        }

        return [self::OK, $mailer.' · '.$from];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function privateDisk(): array
    {
        $diskName = (string) config('lite-crm.documents.disk', 'local');
        $root = (string) config("filesystems.disks.{$diskName}.root", '');

        if (config("filesystems.disks.{$diskName}.driver") === 'local' && $root !== '' && str_starts_with(realpath($root) ?: $root, realpath(public_path()) ?: public_path())) {
            return [self::FAIL, __('lite-crm::doctor.disk_public', ['disk' => $diskName])];
        }

        if (config("filesystems.disks.{$diskName}.visibility") === 'public') {
            return [self::FAIL, __('lite-crm::doctor.disk_public', ['disk' => $diskName])];
        }

        $disk = Storage::disk($diskName);
        $probe = 'lite-crm-doctor-'.Str::random(8).'.txt';
        $disk->put($probe, 'ok');
        $readable = $disk->get($probe) === 'ok';
        $disk->delete($probe);

        return $readable ? [self::OK, $diskName] : [self::FAIL, __('lite-crm::doctor.disk_unwritable', ['disk' => $diskName])];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function enquiryApi(): array
    {
        if (! config('lite-crm.enquiry_api.enabled')) {
            return [self::OK, __('lite-crm::doctor.api_off')];
        }

        return app(ApiToken::class)->exists()
            ? [self::OK, __('lite-crm::doctor.api_on')]
            : [self::WARN, __('lite-crm::doctor.api_no_token')];
    }
}
