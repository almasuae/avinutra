<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LiteCrm\Console\DoctorCommand;
use LiteCrm\Tests\TestCase;

/*
 * Server problems found on a real HestiaCP install: pcntl functions disabled for
 * command-line PHP (the queue worker crashes silently) and scheduler locks left
 * behind by a crashed command (it then stops running for 24 hours).
 */

uses(TestCase::class);

beforeEach(fn () => $this->seedCrm());

it('fails when the queue worker\'s pcntl functions are disabled for command-line PHP', function (): void {
    $hestiaDefault = 'pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_signal,pcntl_async_signals,exec';

    [$status, $details] = DoctorCommand::pcntlStatus(true, $hestiaDefault, '/etc/php/8.3/cli/php.ini', fn (): bool => true);

    expect($status)->toBe(DoctorCommand::FAIL)
        ->and($details)->toContain('pcntl_signal, pcntl_async_signals, pcntl_alarm')
        ->and($details)->toContain('/etc/php/8.3/cli/php.ini');

    expect(DoctorCommand::pcntlStatus(true, 'exec, shell_exec', 'php.ini', fn (): bool => true)[0])->toBe(DoctorCommand::OK)
        ->and(DoctorCommand::pcntlStatus(true, '', 'php.ini', fn (string $f): bool => $f !== 'pcntl_alarm')[0])->toBe(DoctorCommand::FAIL)
        ->and(DoctorCommand::pcntlStatus(false, '', 'php.ini')[0])->toBe(DoctorCommand::WARN);
});

it('reports scheduler locks held long after their command should have finished', function (): void {
    config(['cache.default' => 'database', 'cache.stores.database.lock_table' => 'cache_locks', 'cache.prefix' => 'test-']);

    if (! Schema::hasTable('cache_locks')) {
        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    $event = app(Schedule::class)->command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
    $now = now()->getTimestamp();

    // Taken 2 minutes ago: normal.
    DB::table('cache_locks')->insert(['key' => 'test-'.$event->mutexName(), 'owner' => 'x', 'expiration' => $now + $event->expiresAt * 60 - 120]);
    $this->artisan('lite-crm:doctor')->doesntExpectOutputToContain('schedule:clear-cache');

    // Taken 3 hours ago by a worker that crashed: stuck.
    DB::table('cache_locks')->update(['expiration' => $now + $event->expiresAt * 60 - 3 * 3600]);
    $this->artisan('lite-crm:doctor')
        ->expectsOutputToContain('php artisan schedule:clear-cache')
        ->assertFailed();
});
