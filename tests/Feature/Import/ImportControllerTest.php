<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Catalog\Import\ProcessImport;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The import screens: upload a file, map its columns, check, import, stop —
 * for an administrator who may import, and nobody else.
 */
final class ImportControllerTest extends TestCase
{
    private string $directory;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/atrium-controller-'.uniqid();
        config(['atrium-catalog.import.directory' => $this->directory]);
        File::ensureDirectoryExists($this->directory);
        $this->admin = $this->admin();
        $this->actingAs($this->admin, 'admin');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function routes(): array
    {
        return [
            'index' => ['get', '/admin/catalog-import'],
            'store' => ['post', '/admin/catalog-import'],
            'show' => ['get', '/admin/catalog-import/{id}'],
            'check' => ['put', '/admin/catalog-import/{id}'],
            'run' => ['post', '/admin/catalog-import/{id}/run'],
            'step' => ['post', '/admin/catalog-import/{id}/step'],
            'cancel' => ['post', '/admin/catalog-import/{id}/cancel'],
        ];
    }

    #[DataProvider('routes')]
    public function test_only_an_administrator_who_may_import_gets_in(string $method, string $uri): void
    {
        $import = $this->import();
        $uri = str_replace('{id}', (string) $import->id, $uri);

        $this->actingAs($this->editor(['catalog_products.view', 'catalog_products.create', 'catalog_products.update']), 'admin');
        $this->{$method}($uri)->assertForbidden();

        $this->actingAs($this->editor(['catalog_products.import']), 'admin');
        $this->assertNotSame(403, $this->{$method}($uri)->getStatusCode());
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        auth()->guard('admin')->logout();

        $this->get('/admin/catalog-import')->assertRedirect();
    }

    public function test_the_products_page_links_to_the_import_for_who_may_import(): void
    {
        $link = ['name' => 'import', 'label' => 'Import products', 'href' => route('admin.catalog-import.index')];

        $this->get('/admin/catalog_products')
            ->assertInertia(static fn (AssertableInertia $page) => $page->where('links', [$link]));

        $this->actingAs($this->editor(['catalog_products.view']), 'admin');
        $this->get('/admin/catalog_products')
            ->assertInertia(static fn (AssertableInertia $page) => $page->where('links', []));
    }

    public function test_the_import_link_comes_after_the_links_of_default_priority(): void
    {
        $link = ['name' => 'import', 'label' => 'Import products', 'href' => route('admin.catalog-import.index')];
        $before = ['name' => 'before', 'label' => 'Before', 'href' => '/before'];
        $after = ['name' => 'after', 'label' => 'After', 'href' => '/after'];
        Hook::filter('atrium.module.links', static fn (array $links): array => [...$links, $before], 19);
        Hook::filter('atrium.module.links', static fn (array $links): array => [...$links, $after], 20);

        $this->get('/admin/catalog_products')
            ->assertInertia(static fn (AssertableInertia $page) => $page->where('links', [$before, $link, $after]));
    }

    public function test_the_index_lists_the_latest_imports(): void
    {
        $this->freezeTime();
        $console = $this->import(['original_name' => 'cron.csv']);
        $mine = $this->import(['admin_user_id' => $this->admin->id, 'original_name' => 'mine.csv']);
        $mine->forceFill(['started_at' => now()->subMinute(), 'finished_at' => now()])->save();

        foreach (range(1, 19) as $number) {
            $this->import(['original_name' => "more-{$number}.csv"]);
        }

        $this->get('/admin/catalog-import')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Catalog/Import/Index', false)
                ->has('imports', 20)
                ->where('imports.0.original_name', 'more-19.csv')
                ->where('imports.19.original_name', 'mine.csv')
                ->where('imports.19.id', $mine->id)
                ->where('imports.19.user', 'Admin')
                ->where('imports.18.user', null)
                ->where('imports.19.created_at', now()->toIso8601String())
                ->where('imports.19.started_at', now()->subMinute()->toIso8601String())
                ->where('imports.19.finished_at', now()->toIso8601String())
                ->where('imports.18.started_at', null)
                ->where('imports.18.finished_at', null)
                ->where('maxSize', 51200)
                ->where('strings.title', 'Import products'));

        $this->assertNotNull($console->id);
    }

    public function test_an_upload_is_read_and_its_columns_guessed(): void
    {
        $name = 'П'.str_repeat('я', 300).'.csv';
        $response = $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent($name, "Наименование;Артикул;Что-то\nParka;A-1;x\n")]);

        $import = CatalogImport::query()->sole();
        $response->assertRedirect(route('admin.catalog-import.show', $import));
        $this->assertSame(CatalogImport::NEW, $import->status);
        $this->assertSame($this->admin->id, $import->admin_user_id);
        $this->assertSame('П'.str_repeat('я', 254), $import->original_name);
        $this->assertSame(['Наименование', 'Артикул', 'Что-то'], $import->headers);
        $this->assertSame(['name', 'article', null], $import->mapping);
        $this->assertNull($import->options);
        $this->assertSame([1, 'csv', ';'], [$import->total, $import->format, $import->delimiter]);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.csv$/', $import->file);
        $this->assertFileExists($this->directory.'/'.$import->file);
    }

    public function test_an_upload_takes_the_mapping_of_the_last_import_of_the_same_columns(): void
    {
        $this->import(['headers' => ['Name', 'SKU'], 'mapping' => ['name', null], 'options' => ['key' => 'code']]);
        $this->import(['headers' => ['Name', 'SKU'], 'mapping' => [null, 'article'], 'options' => ['key' => 'external_id']]);
        $this->import(['headers' => ['Name', 'SKU'], 'mapping' => null]);
        $this->import(['headers' => ['Other'], 'mapping' => [null]]);

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name;SKU\nParka;A-1\n")]);

        $import = CatalogImport::query()->latest('id')->first();
        $this->assertSame([null, 'article'], $import?->mapping);
        $this->assertSame(['key' => 'external_id'], $import?->options);
    }

    public function test_the_twentieth_import_back_is_still_looked_at(): void
    {
        $this->import(['headers' => ['Name', 'SKU'], 'mapping' => [null, 'article'], 'options' => ['key' => 'code']]);

        foreach (range(1, 19) as $number) {
            $this->import(['headers' => ["Column {$number}"], 'mapping' => [null]]);
        }

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name;SKU\nParka;A-1\n")]);

        $this->assertSame([null, 'article'], CatalogImport::query()->latest('id')->first()?->mapping);
    }

    public function test_the_directory_is_made_when_it_is_not_there(): void
    {
        File::deleteDirectory($this->directory);

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name\nParka\n")])->assertSessionHasNoErrors();

        $this->assertFileExists(CatalogImport::query()->sole()->path());
    }

    public function test_the_error_names_the_file_in_the_language_of_the_admin(): void
    {
        $this->admin->update(['locale' => 'ru']);

        $this->post('/admin/catalog-import', [])->assertSessionHasErrors(['file' => 'Заполните поле «файл».']);
    }

    public function test_an_import_older_than_the_last_twenty_is_not_looked_at(): void
    {
        $this->import(['headers' => ['Name', 'SKU'], 'mapping' => [null, null], 'options' => ['key' => 'code']]);

        foreach (range(1, 20) as $number) {
            $this->import(['headers' => ["Column {$number}"], 'mapping' => [null]]);
        }

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name;SKU\nParka;A-1\n")]);

        $this->assertSame(['name', 'article'], CatalogImport::query()->latest('id')->first()?->mapping);
    }

    public function test_an_upload_needs_a_file_within_the_limit(): void
    {
        config(['atrium-catalog.import.max_size' => 1]);

        $this->post('/admin/catalog-import', [])->assertSessionHasErrors(['file' => 'The file field is required.']);
        $this->post('/admin/catalog-import', ['file' => 'text'])->assertSessionHasErrors(['file' => 'The file field must be a file.']);
        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', str_repeat("a\n", 600))])
            ->assertSessionHasErrors(['file' => 'The file field must not be greater than 1 kilobytes.']);
        $this->assertSame(0, CatalogImport::query()->count());
    }

    public function test_a_file_that_cannot_be_read_is_refused_and_not_kept(): void
    {
        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.xls', "\xD0\xCF\x11\xE0\x00\x01\x02\x03\x00")])
            ->assertSessionHasErrors(['file' => 'This is not a CSV or Excel (xlsx) file. An old .xls file can be saved as .xlsx or CSV in Excel.']);

        $this->assertSame(0, CatalogImport::query()->count());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_file_with_nothing_under_the_header_is_refused_and_not_kept(): void
    {
        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('a.csv', "Name;SKU\n")])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, CatalogImport::query()->count());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_the_mapping_screen_shows_the_first_rows_and_what_a_column_can_fill(): void
    {
        $import = $this->import(['headers' => ['Name'], 'mapping' => ['name'], 'options' => ['mode' => 'update']]);
        File::put($import->path(), implode('', array_map(static fn (int $row): string => "{$row},Product {$row}\n", range(2, 8))));

        $this->get('/admin/catalog-import/'.$import->id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Catalog/Import/Show', false)
                ->where('import.id', $import->id)
                ->where('import.options', ['key' => 'article', 'mode' => 'update', 'create_sections' => false, 'separator' => '/'])
                ->where('preview', [['Product 2'], ['Product 3'], ['Product 4'], ['Product 5'], ['Product 6']])
                ->where('targets.0', ['key' => 'active', 'label' => 'Active'])
                ->where('keys', [['key' => 'article', 'label' => 'Article number'], ['key' => 'code', 'label' => 'Code'], ['key' => 'external_id', 'label' => 'External ID']])
                ->where('modes', ['both', 'create', 'update'])
                ->where('strings.show.check', 'Check'));
    }

    public function test_the_mapping_screen_of_an_import_whose_file_is_gone(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields, string $model): array => $model === Product::class
            ? array_values(array_filter($fields, static fn ($field): bool => $field->getName() !== 'external_id'))
            : $fields);
        $import = $this->import(['status' => CatalogImport::DONE]);

        $this->get('/admin/catalog-import/'.$import->id)
            ->assertInertia(static fn (AssertableInertia $page) => $page
                ->where('preview', [])
                ->where('keys.2', ['key' => 'external_id', 'label' => 'external_id']));
    }

    public function test_an_unknown_import_is_not_found(): void
    {
        $this->get('/admin/catalog-import/999')->assertNotFound();
        $this->get('/admin/catalog-import/abc')->assertNotFound();
    }

    public function test_the_check_saves_the_mapping_and_starts(): void
    {
        $import = $this->import(['headers' => ['Article', 'Name'], 'status' => CatalogImport::CHECKED]);

        $this->put('/admin/catalog-import/'.$import->id, ['mapping' => ['article', 'name'], 'key' => 'article', 'mode' => 'create', 'create_sections' => '1', 'separator' => '>'])
            ->assertRedirect(route('admin.catalog-import.show', $import));

        $import->refresh();
        $this->assertSame(CatalogImport::CHECKING, $import->status);
        $this->assertSame(['article', 'name'], $import->mapping);
        $this->assertSame(['key' => 'article', 'mode' => 'create', 'create_sections' => true, 'separator' => '>'], $import->options);
        $this->assertNotNull($import->started_at);
    }

    public function test_a_queue_worker_is_handed_the_check_and_the_import(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);
        $import = $this->import(['headers' => ['Article']]);

        $this->put('/admin/catalog-import/'.$import->id, ['mapping' => ['article'], 'key' => 'article', 'mode' => 'both', 'separator' => '/']);
        Queue::assertPushed(ProcessImport::class, static fn (ProcessImport $job): bool => $job->import === $import->id);

        $import->forceFill(['status' => CatalogImport::CHECKED])->save();
        $this->post('/admin/catalog-import/'.$import->id.'/run');
        Queue::assertPushed(ProcessImport::class, 2);
    }

    public function test_without_a_queue_nothing_is_queued(): void
    {
        Queue::fake();
        $import = $this->import(['headers' => ['Article']]);

        $this->put('/admin/catalog-import/'.$import->id, ['mapping' => ['article'], 'key' => 'article', 'mode' => 'both', 'separator' => '/']);

        Queue::assertNothingPushed();
    }

    public function test_the_check_refuses_a_mapping_that_does_not_fit(): void
    {
        $import = $this->import(['headers' => ['Article', 'Name']]);

        $this->put('/admin/catalog-import/'.$import->id, ['mapping' => ['article'], 'key' => 'article', 'mode' => 'both', 'separator' => '/'])
            ->assertSessionHasErrors('mapping');

        $this->assertSame(CatalogImport::NEW, $import->refresh()->status);
        $this->assertNull($import->mapping);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function closed(): array
    {
        return ['checking' => [CatalogImport::CHECKING], 'importing' => [CatalogImport::IMPORTING], 'done' => [CatalogImport::DONE], 'cancelled' => [CatalogImport::CANCELLED]];
    }

    #[DataProvider('closed')]
    public function test_a_running_or_finished_import_is_not_checked_again(string $status): void
    {
        $import = $this->import(['headers' => ['Article'], 'status' => $status]);

        $this->put('/admin/catalog-import/'.$import->id, ['mapping' => ['article'], 'key' => 'article', 'mode' => 'both', 'separator' => '/'])
            ->assertStatus(409);
        $this->assertSame($status, $import->refresh()->status);
    }

    public function test_the_import_starts_after_the_check(): void
    {
        $import = $this->import(['status' => CatalogImport::CHECKED]);
        $import->forceFill(['created' => 5])->save();

        $this->post('/admin/catalog-import/'.$import->id.'/run')->assertRedirect(route('admin.catalog-import.show', $import));

        $import->refresh();
        $this->assertSame(CatalogImport::IMPORTING, $import->status);
        $this->assertSame(0, $import->created);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function unchecked(): array
    {
        return ['new' => [CatalogImport::NEW], 'checking' => [CatalogImport::CHECKING], 'failed' => [CatalogImport::FAILED], 'done' => [CatalogImport::DONE]];
    }

    #[DataProvider('unchecked')]
    public function test_the_import_does_not_start_without_a_check(string $status): void
    {
        $import = $this->import(['status' => $status]);

        $this->post('/admin/catalog-import/'.$import->id.'/run')->assertStatus(409);
        $this->assertSame($status, $import->refresh()->status);
    }

    public function test_without_a_queue_the_progress_page_moves_the_import(): void
    {
        config(['atrium-catalog.import.request_seconds' => 0]);
        $import = $this->running();

        $this->postJson('/admin/catalog-import/'.$import->id.'/step')
            ->assertOk()
            ->assertJson(['id' => $import->id, 'status' => CatalogImport::IMPORTING, 'processed' => 1, 'created' => 1, 'options' => ['key' => 'article']]);
    }

    public function test_with_a_queue_the_progress_page_only_reads_where_it_stands(): void
    {
        config(['queue.default' => 'database']);
        $import = $this->running();

        $this->postJson('/admin/catalog-import/'.$import->id.'/step')
            ->assertOk()
            ->assertJson(['status' => CatalogImport::IMPORTING, 'processed' => 0]);
    }

    public function test_an_import_can_be_stopped_and_its_file_goes(): void
    {
        $import = $this->running();

        $this->post('/admin/catalog-import/'.$import->id.'/cancel')->assertRedirect(route('admin.catalog-import.show', $import));

        $this->assertSame(CatalogImport::CANCELLED, $import->refresh()->status);
        $this->assertNotNull($import->finished_at);
        $this->assertFileDoesNotExist($import->path());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_stopping_waits_for_the_step_under_way_and_reads_the_import_after_it(): void
    {
        config(['atrium-catalog.import.seconds' => 20]);
        $import = $this->running();
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('block')->once()->with(25, Mockery::type(Closure::class))->andReturnUsing(static function (int $seconds, Closure $then) use ($import): mixed {
            // The step under way finished the import while this waited.
            CatalogImport::query()->whereKey($import->id)->update(['status' => CatalogImport::DONE]);

            return $then();
        });
        Cache::partialMock()->shouldReceive('lock')->once()->with('atrium-catalog-import:'.$import->id, 10)->andReturn($lock);

        $this->post('/admin/catalog-import/'.$import->id.'/cancel')->assertRedirect();

        $this->assertSame(CatalogImport::DONE, $import->refresh()->status);
        $this->assertFileExists($import->path());
    }

    public function test_a_finished_import_stays_finished(): void
    {
        $import = $this->import(['status' => CatalogImport::DONE]);

        $this->post('/admin/catalog-import/'.$import->id.'/cancel');

        $this->assertSame(CatalogImport::DONE, $import->refresh()->status);
    }

    private function running(): CatalogImport
    {
        $import = $this->import(['headers' => ['Article', 'Name'], 'mapping' => ['article', 'name']]);
        File::put($import->path(), "2,A-1,Parka\n3,A-2,Boots\n");
        $import->begin(CatalogImport::IMPORTING);

        return $import;
    }

    /**
     * @param  array<int,string>  $permissions
     */
    private function editor(array $permissions): AdminUser
    {
        $user = AdminUser::query()->create(['name' => 'Editor', 'email' => uniqid().'@example.com', 'password' => 'secret-password', 'is_active' => true]);
        $user->roles()->attach(AdminRole::query()->create(['name' => uniqid(), 'permissions' => $permissions]));

        return $user;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function import(array $attributes = []): CatalogImport
    {
        $import = CatalogImport::query()->create([
            'status' => CatalogImport::NEW, 'original_name' => 'a.csv', 'file' => uniqid().'.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => ['Name'], 'total' => 2,
            ...$attributes,
        ]);

        return $import->refresh();
    }
}
