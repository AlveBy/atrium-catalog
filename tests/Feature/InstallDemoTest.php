<?php

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Resources\ResourceRegistry;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Atrium\News\Models\News;
use Atrium\Stores\Models\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * Stage 6a acceptance: one command installs the module packages Composer
 * has and fills them with the demo shop.
 */
final class InstallDemoTest extends TestCase
{
    private string $root;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Publish targets live in static properties: an app booted earlier in
        // the same process (the root app's suite) would have left its own
        // paths there, and the install would publish into the repository.
        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        // The install writes .env, publishes config, assets and migrations and
        // links the storage. Publish targets are fixed when the providers
        // boot, so the folders are swapped before that, never into the
        // Testbench skeleton or the repository.
        $this->root = sys_get_temp_dir().'/atrium-demo-'.uniqid();

        foreach (['config', 'public', 'database/migrations'] as $dir) {
            mkdir($this->root.'/'.$dir, 0777, true);
        }

        file_put_contents($this->root.'/.env', "APP_NAME=Shop\n");

        $app->useEnvironmentPath($this->root);
        $app->useConfigPath($this->root.'/config');
        $app->usePublicPath($this->root.'/public');
        $app->useDatabasePath($this->root.'/database');
        $app['config']->set('filesystems.links', [$this->root.'/public/storage' => $this->root.'/storage']);
        // Required with Composer, not installed yet.
        $app['config']->set('atrium.modules', []);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_install_without_demo_links_the_storage_for_uploads(): void
    {
        $this->artisan('atrium:install', ['--name' => 'Ada', '--email' => 'ada@example.com', '--password' => 'correct-horse'])
            ->assertSuccessful();

        $this->assertTrue(is_link($this->root.'/public/storage'));
        $this->assertSame([], config('atrium.modules'));
    }

    public function test_install_with_demo_installs_the_modules_and_fills_them_once(): void
    {
        $this->assertArrayNotHasKey('catalog_products', $this->app->make(ResourceRegistry::class)->all());

        $this->artisan('atrium:install', ['--demo' => true, '--name' => 'Ada', '--email' => 'ada@example.com', '--password' => 'correct-horse'])
            ->assertSuccessful();

        $config = (string) file_get_contents($this->root.'/config/atrium.php');

        foreach (['catalog', 'brands', 'stores', 'news'] as $slug) {
            $this->assertStringContainsString("'alveby/atrium-{$slug}',", $config);
            $this->assertContains("alveby/atrium-{$slug}", config('atrium.modules'));
        }

        $this->assertArrayHasKey('catalog_products', $this->app->make(ResourceRegistry::class)->all());
        $this->assertSame(['prefix' => 'catalog', 'tree' => CatalogSection::class, 'parent' => 'section_id'], config('atrium.public_urls.'.Product::class));

        $this->assertSame(8, Product::query()->count());
        $this->assertSame('Lumen', Product::query()->where('code', 'aero-14')->sole()->brand?->getAttribute('name'));
        $this->assertSame(7, CatalogSection::query()->count());
        $this->assertSame(3, Brand::query()->count());
        $this->assertSame(4, News::query()->count());
        $this->assertSame(2, Store::query()->count());
        $this->assertSame(['de', 'en', 'ru'], Language::query()->orderBy('code')->pluck('code')->all());
        $this->assertSame(['de', 'en'], Site::query()->where('code', 'outlet')->value('languages'));
        Storage::disk('public')->assertExists(['demo/laptop.svg', 'demo/lumen.svg', 'demo/quick-start.pdf']);
        $this->assertTrue(is_link($this->root.'/public/storage'));

        // A second run leaves the content alone.
        Product::query()->where('code', 'aero-14')->update(['price' => 999]);

        $this->artisan('atrium:install', ['--demo' => true])
            ->expectsOutputToContain('already has content')
            ->assertSuccessful();

        $this->assertSame(8, Product::query()->count());
        $this->assertSame('999.00', Product::query()->where('code', 'aero-14')->value('price'));
    }
}
