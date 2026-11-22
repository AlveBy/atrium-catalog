<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Models\DataImport;
use Atrium\Admin\Transfer\Hosts;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A price list becomes products: the console command `atrium:import
 * catalog_products` end to end, from CSV and from Excel.
 */
final class ProductImportTest extends ProductTransferTestCase
{
    /**
     * @return array<string,array{string}>
     */
    public static function formats(): array
    {
        return ['csv' => ['csv'], 'xlsx' => ['xlsx']];
    }

    #[DataProvider('formats')]
    public function test_a_price_list_creates_the_products_with_all_it_says(string $format): void
    {
        $jackets = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'active' => true]);
        $clothes = CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $jackets->update(['parent_id' => $clothes->id]);
        Storage::disk('public')->put('catalog/1.jpg', 'x');
        Storage::disk('public')->put('catalog/2.jpg', 'x');
        Storage::disk('public')->put('catalog/3.jpg', 'x');

        $path = $this->table($format, [
            ['Артикул', 'Наименование', 'Цена', 'Внешний код', 'Картинка', 'Галерея', 'Категория'],
            ['A-1', 'Parka', '1299.5', 'ext-1', 'catalog/1.jpg', 'catalog/2.jpg|catalog/3.jpg', 'Одежда / Куртки'],
            ['A-2', 'Boots', '90', 'ext-2', '', '', 'Одежда'],
        ]);

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article'])
            ->expectsOutput('Rows: 2. Created: 2, updated: 0, skipped: 0, errors: 0, tree items created: 0.')
            ->assertExitCode(0);

        $parka = Product::query()->where('article', 'A-1')->sole();
        $boots = Product::query()->where('article', 'A-2')->sole();

        $this->assertSame(['Parka', 'parka', '1299.50', 'ext-1', true, 500], [$parka->name, $parka->code, $parka->price, $parka->external_id, $parka->active, $parka->sort]);
        $this->assertSame(['catalog/1.jpg', ['catalog/2.jpg', 'catalog/3.jpg'], $jackets->id], [$parka->preview_picture, $parka->photos, $parka->section_id]);
        $this->assertSame(['Boots', 'boots', '90.00', 'ext-2', null, null, $clothes->id], [$boots->name, $boots->code, $boots->price, $boots->external_id, $boots->preview_picture, $boots->photos, $boots->section_id]);
        $this->assertSame(2, Product::query()->count());

        $import = DataImport::query()->sole();
        $this->assertSame(['catalog_products', DataImport::DONE, 'table.'.$format, $format], [$import->module, $import->status, $import->original_name, $import->format]);
        $this->assertSame(['article', 'name', 'price', 'external_id', 'preview_picture', 'photos', 'section_id'], $import->mapping);
        $this->assertSame([2, 2, 0, 0, 0, 0], [$import->total, $import->created, $import->updated, $import->skipped, $import->failed, $import->nodes]);
        $this->assertSame([], File::files($this->directory), 'The file of an import that is over is deleted.');
    }

    public function test_a_known_key_updates_the_product_and_a_new_one_creates_it(): void
    {
        $parka = Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100, 'package' => 'Box', 'external_id' => 'ext-1']);
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;;150\nA-9;Hat;7\n");

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article'])
            ->expectsOutput('Rows: 2. Created: 1, updated: 1, skipped: 0, errors: 0, tree items created: 0.')
            ->assertExitCode(0);

        $this->assertSame(2, Product::query()->count());
        $parka->refresh();
        // An empty cell leaves the field as it is; what the file does not mention stays.
        $this->assertSame(['Parka', '150.00', 'Box', 'ext-1'], [$parka->name, $parka->price, $parka->package, $parka->external_id]);
        $hat = Product::query()->where('article', 'A-9')->sole();
        $this->assertSame([$parka->id + 1, 'Hat', '7.00'], [$hat->id, $hat->name, $hat->price]);
    }

    public function test_importing_the_same_file_twice_creates_nothing_the_second_time(): void
    {
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;Parka;100\nA-2;Boots;90\n");

        $this->importFile($path, ['--key' => 'article']);
        $second = $this->importFile($path, ['--key' => 'article']);

        $this->assertSame([0, 2], [$second->created, $second->updated]);
        $this->assertSame(2, Product::query()->count());
    }

    public function test_only_new_products_are_created_in_the_create_mode(): void
    {
        Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;Parka;999\nA-2;Boots;90\n");

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article', '--mode' => 'create'])
            ->expectsOutput('Rows: 2. Created: 1, updated: 0, skipped: 1, errors: 0, tree items created: 0.')
            ->assertExitCode(0);

        $this->assertSame('100.00', Product::query()->where('article', 'A-1')->sole()->price);
        $this->assertSame(2, Product::query()->count());
        $this->assertSame([['row' => 2, 'skipped' => true, 'messages' => ['The record "A-1" is already there, and only new records are created.']]], DataImport::query()->sole()->errors);
    }

    public function test_only_known_products_are_updated_in_the_update_mode(): void
    {
        Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;Parka;120\nA-2;Boots;90\n");

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article', '--mode' => 'update'])
            ->expectsOutput('Rows: 2. Created: 0, updated: 1, skipped: 1, errors: 0, tree items created: 0.')
            ->assertExitCode(0);

        $this->assertSame('120.00', Product::query()->where('article', 'A-1')->sole()->price);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame([['row' => 3, 'skipped' => true, 'messages' => ['There is no record "A-2", and only existing records are updated.']]], DataImport::query()->sole()->errors);
    }

    public function test_a_check_counts_what_would_happen_and_writes_nothing(): void
    {
        Product::query()->create(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;Parka;120\nA-2;Boots;90\nA-2;Boots;95\n");

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article', '--check' => true])
            ->expectsOutput('Rows: 3. Created: 1, updated: 2, skipped: 0, errors: 0, tree items created: 0.')
            ->assertExitCode(0);

        $this->assertSame(['100.00'], Product::query()->pluck('price')->all());
        $this->assertSame(DataImport::CHECKED, DataImport::query()->sole()->status);
        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_row_that_cannot_be_saved_fails_with_its_reasons_and_the_rest_goes_on(): void
    {
        $path = $this->file('price.csv', "Article;Name;Price\nA-1;;cheap\n;Boots;90\nA-3;Hat;5\n");

        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, '--key' => 'article'])
            ->expectsOutput('Rows: 3. Created: 1, updated: 0, skipped: 0, errors: 2, tree items created: 0.')
            ->expectsOutput('Row 2: The name field is required. The price field must be a number.')
            ->expectsOutput('Row 3: "Article number" is empty: the record cannot be found or created.')
            ->assertExitCode(1);

        $this->assertSame(['A-3'], Product::query()->pluck('article')->all());
    }

    public function test_a_photo_by_address_is_downloaded_into_the_media_library_once(): void
    {
        $this->app->instance(Hosts::class, new Hosts(static fn (string $host): array => ['93.184.216.34']));
        Http::fake(['https://cdn.example.com/*' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 200, ['Content-Type' => 'image/png'])]);
        $path = $this->file('price.csv', "Article;Name;Photos\nA-1;Parka;https://cdn.example.com/a.png\nA-2;Boots;https://cdn.example.com/a.png\n");

        $this->importFile($path, ['--key' => 'article']);

        $photos = Product::query()->orderBy('article')->pluck('photos')->all();
        $this->assertCount(1, $photos[0]);
        $this->assertSame($photos[0], $photos[1]);
        $this->assertMatchesRegularExpression('#^/storage/import/\d{4}/\d{2}/[0-9a-f-]{36}\.png$#', $photos[0][0]);
        Http::assertSentCount(1);
    }

    public function test_a_photo_that_is_not_on_the_site_fails_the_row(): void
    {
        $path = $this->file('price.csv', "Article;Name;Photos\nA-1;Parka;catalog/missing.jpg\n");

        $import = $this->importFile($path, ['--key' => 'article']);

        $this->assertSame([0, 1], [$import->created, $import->failed]);
        $this->assertSame([['row' => 2, 'skipped' => false, 'messages' => ['There is no image catalog/missing.jpg on the site.']]], $import->errors);
        $this->assertSame(0, Product::query()->count());
    }
}
