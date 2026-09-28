<?php

declare(strict_types=1);

namespace LiteCrm\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kirschbaum\PowerJoins\PowerJoinsServiceProvider;
use LiteCrm\Database\Seeders\LiteCrmSeeder;
use LiteCrm\LiteCrmServiceProvider;
use LiteCrm\Models\UserProfile;
use LiteCrm\Tests\Fixtures\TestPanelProvider;
use LiteCrm\Tests\Fixtures\User;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\PermissionServiceProvider;

use function Orchestra\Testbench\default_migration_path;
use function Orchestra\Testbench\load_migration_paths;

/**
 * Boots a bare Laravel application with Filament and the package only,
 * so the package is always tested without the host application.
 *
 * Runs on in-memory SQLite, or on MariaDB when DB_CONNECTION=mariadb
 * (see composer test:mariadb in the host).
 */
abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            PowerJoinsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            QueryBuilderServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            PermissionServiceProvider::class,
            ActivitylogServiceProvider::class,
            LiteCrmServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', env('DB_CONNECTION') === 'mariadb' ? 'mariadb' : 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('lite-crm.user_model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Registered as paths, so RefreshDatabase includes them in migrate:fresh.
        // Always as migrator paths, on SQLite and MariaDB alike.
        load_migration_paths($this->app, [default_migration_path(), __DIR__.'/database/migrations']);
    }

    public function seedCrm(): void
    {
        $this->seed(LiteCrmSeeder::class);
    }

    /**
     * An active CRM user with the given roles.
     *
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $profile
     */
    public function crmUser(array $roles = ['commercial'], array $profile = [], ?string $email = null): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => 'Test '.implode(' ', $roles),
            'email' => $email ?? fake()->unique()->safeEmail(),
            'password' => 'correct-horse-battery',
        ]);

        /** @var UserProfile $crmProfile */
        $crmProfile = $user->crmProfile()->make($profile);
        $crmProfile->save();

        $user->syncRoles($roles);

        return $user->refresh();
    }

    /**
     * Marks MFA as set up, so users who must use MFA are not redirected.
     */
    public function withMfa(Model $user): Model
    {
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');

        return $user;
    }
}
