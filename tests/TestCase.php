<?php

declare(strict_types=1);

namespace Atrium\Catalog\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Brands\BrandsServiceProvider;
use Atrium\Catalog\CatalogServiceProvider;
use Atrium\News\NewsServiceProvider;
use Atrium\Stores\StoresServiceProvider;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * On MySQL and PostgreSQL the whole suite shares one database: each
     * class migrates it again with the modules it installs.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        RefreshDatabaseState::$migrated = false;
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            ThemeServiceProvider::class,
            CatalogServiceProvider::class,
            BrandsServiceProvider::class,
            StoresServiceProvider::class,
            NewsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Demo shop');
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.seo.title_suffix', ' | Demo shop');
        // The whole demo shop; a test that wants fewer modules says so.
        $config->set('atrium.modules', ['alveby/atrium-catalog', 'alveby/atrium-brands', 'alveby/atrium-news', 'alveby/atrium-stores']);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
        PublicUrl::forgetPaths();
    }

    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * Every installed module's demo, as `atrium:install --demo` fills it.
     */
    protected function seedDemo(): void
    {
        $modules = $this->app->make(Modules::class);

        foreach ($modules->dependencyOrder($modules->installed()) as $module) {
            $modules->seedDemo($module);
        }
    }

    protected function root(): string
    {
        return rtrim(url('/'), '/');
    }
}
