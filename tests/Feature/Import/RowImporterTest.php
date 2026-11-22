<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Fields\MultiSelectField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Import\Hosts;
use Atrium\Catalog\Import\RowImporter;
use Atrium\Catalog\Import\Targets;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Closure;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * A row of the file becomes a product the way the form would save it; a
 * check says what the import would do and writes nothing.
 */
final class RowImporterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_a_row_creates_a_product_with_every_kind_of_field(): void
    {
        $brand = Brand::query()->create(['name' => 'Acme', 'code' => 'acme', 'active' => true]);
        $section = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'active' => true]);
        Storage::disk('public')->put('catalog/1.jpg', 'x');
        Storage::disk('public')->put('catalog/2.jpg', 'x');
        Storage::disk('public')->put('catalog/3.jpg', 'x');

        $import = $this->import(['article', 'name', 'price', 'active', 'active_from', 'released_on', 'label', 'colors', 'in_stock', 'features', 'preview_picture', 'photos', 'section', 'brand_id', 'package', 'sort']);
        $this->importer($import)->import(2, ['A-1', 'Parka', '1 299,50', 'нет', '05.03.2026 10:30', '05.03.2026', 'Bestseller', 'black | Silver', 'да', "Warm\nLight", 'catalog/1.jpg', 'catalog/2.jpg, catalog/3.jpg', 'куртки', 'ACME', 'Parka, hood', '10']);

        $this->assertSame([1, 1, 0, 0, 0], [$import->processed, $import->created, $import->updated, $import->failed, $import->skipped]);
        $this->assertSame([], $import->errors);

        $product = Product::query()->sole();
        $this->assertSame('A-1', $product->article);
        $this->assertSame('Parka', $product->name);
        $this->assertSame('parka', $product->code);
        $this->assertSame('1299.50', $product->price);
        $this->assertFalse($product->active);
        $this->assertSame('2026-03-05 10:30:00', $product->active_from?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-05', $product->released_on?->format('Y-m-d'));
        $this->assertSame('hit', $product->label);
        $this->assertSame(['black', 'silver'], $product->colors);
        $this->assertSame('Y', $product->in_stock);
        $this->assertSame(['Warm', 'Light'], $product->features);
        $this->assertSame('catalog/1.jpg', $product->preview_picture);
        $this->assertSame(['catalog/2.jpg', 'catalog/3.jpg'], $product->photos);
        $this->assertSame($section->id, $product->section_id);
        $this->assertSame($brand->id, (int) $product->brand_id);
        $this->assertSame('Parka, hood', $product->package);
        $this->assertSame(10, $product->sort);
    }

    public function test_a_row_updates_the_product_with_its_key_and_leaves_the_rest(): void
    {
        $product = $this->product(['article' => 'A-1', 'name' => 'Parka', 'price' => 100, 'package' => 'Box']);

        $import = $this->import(['article', 'name', 'price', null, 'package']);
        $this->importer($import)->import(2, ['A-1', '', '150', 'ignored', '']);

        $this->assertSame([1, 0, 1], [$import->processed, $import->created, $import->updated]);
        $product->refresh();
        $this->assertSame('Parka', $product->name);
        $this->assertSame('150.00', $product->price);
        $this->assertSame('Box', $product->package);
        $this->assertSame(1, Product::query()->count());
    }

    public function test_a_short_row_leaves_the_missing_cells_empty(): void
    {
        $product = $this->product(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);

        $import = $this->import(['article', 'price']);
        $this->importer($import)->import(2, ['A-1']);

        $this->assertSame(1, $import->updated);
        $this->assertSame('100.00', $product->refresh()->price);
    }

    public function test_the_first_of_two_products_with_the_same_key_is_updated(): void
    {
        $first = $this->product(['article' => 'A-1', 'name' => 'First', 'code' => 'first']);
        $second = $this->product(['article' => 'A-1', 'name' => 'Second', 'code' => 'second']);

        $import = $this->import(['article', 'price']);
        $this->importer($import)->import(2, ['A-1', '5']);

        $this->assertSame('5.00', $first->refresh()->price);
        $this->assertNull($second->refresh()->price);
    }

    public function test_products_are_found_by_the_key_the_import_names(): void
    {
        $byCode = $this->product(['code' => 'parka', 'name' => 'Parka', 'article' => 'X']);
        $byExternal = $this->product(['code' => 'boots', 'name' => 'Boots', 'external_id' => '1c-7']);

        $import = $this->import(['code', 'external_id', 'price'], ['key' => 'code']);
        $this->importer($import)->import(2, ['parka', '', '7']);
        $this->assertSame('7.00', $byCode->refresh()->price);

        $import = $this->import(['code', 'external_id', 'price'], ['key' => 'external_id']);
        $this->importer($import)->import(2, ['', '1c-7', '8']);
        $this->assertSame('8.00', $byExternal->refresh()->price);
        $this->assertSame(2, Product::query()->count());
    }

    public function test_a_row_without_the_key_fails(): void
    {
        $import = $this->import(['article', 'name']);
        $this->importer($import)->import(5, ['', 'Parka']);

        $this->assertSame([1, 1, 0], [$import->processed, $import->failed, $import->created]);
        $this->assertSame([['row' => 5, 'skipped' => false, 'messages' => ['"Article number" is empty: the product cannot be found or created.']]], $import->errors);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_create_only_skips_a_product_that_is_there_and_creates_a_new_one(): void
    {
        $product = $this->product(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);

        $import = $this->import(['article', 'name', 'price'], ['mode' => 'create']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', '150']);
        $importer->import(3, ['A-2', 'Boots', '90']);

        $this->assertSame([2, 1, 0, 1], [$import->processed, $import->created, $import->updated, $import->skipped]);
        $this->assertSame([['row' => 2, 'skipped' => true, 'messages' => ['The product "A-1" is already there, and only new products are created.']]], $import->errors);
        $this->assertSame('100.00', $product->refresh()->price);
        $this->assertTrue(Product::query()->where('article', 'A-2')->exists());
    }

    public function test_update_only_skips_a_new_product_and_updates_one_that_is_there(): void
    {
        $product = $this->product(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);

        $import = $this->import(['article', 'name', 'price'], ['mode' => 'update']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-2', 'Boots', '90']);
        $importer->import(3, ['A-1', 'Parka', '150']);

        $this->assertSame([2, 0, 1, 1], [$import->processed, $import->created, $import->updated, $import->skipped]);
        $this->assertSame([['row' => 2, 'skipped' => true, 'messages' => ['There is no product "A-2", and only existing products are updated.']]], $import->errors);
        $this->assertSame('150.00', $product->refresh()->price);
        $this->assertSame(1, Product::query()->count());
    }

    public function test_an_import_does_not_count_on_what_a_check_remembered(): void
    {
        $import = $this->import(['article', 'name'], ['mode' => 'update']);
        $import->remember('product', 'A-1');
        $this->importer($import)->import(2, ['A-1', 'Parka']);

        $this->assertSame(1, $import->skipped);
    }

    public function test_a_row_the_form_would_refuse_fails_with_the_reasons(): void
    {
        $import = $this->import(['article', 'price']);
        $this->importer($import)->import(2, ['A-1', '12']);

        $this->assertSame([1, 1, 0], [$import->processed, $import->failed, $import->created]);
        $this->assertSame(['The name field is required.'], $import->errors[0]['messages']);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_row_the_database_refuses_fails_and_is_logged(): void
    {
        Exceptions::fake();
        Product::saving(static fn () => throw new QueryException('testing', 'insert', [], new Exception('Deadlock')));

        $import = $this->import(['article', 'name']);
        $this->importer($import)->import(2, ['A-1', 'Parka']);

        $this->assertSame([1, 1], [$import->processed, $import->failed]);
        $this->assertSame(['The database refused the row. The error has been logged.'], $import->errors[0]['messages']);
        Exceptions::assertReported(QueryException::class);
    }

    public function test_a_value_that_is_neither_yes_nor_no_fails(): void
    {
        $import = $this->import(['article', 'name', 'active']);
        $this->importer($import)->import(2, ['A-1', 'Parka', 'maybe']);

        $this->assertSame(['"Active": "maybe" is neither yes nor no.'], $import->errors[0]['messages']);
    }

    public function test_an_option_is_found_by_value_or_by_label(): void
    {
        $import = $this->import(['article', 'name', 'label']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', 'sale']);
        $importer->import(3, ['A-2', 'Boots', 'BESTSELLER!']);
        $importer->import(4, ['A-3', 'Hat', ' NEW ']);

        $this->assertSame([], $import->errors);
        $this->assertSame('sale', Product::query()->where('article', 'A-1')->value('label'));
        $this->assertSame('hit', Product::query()->where('article', 'A-2')->value('label'));
        $this->assertSame('new', Product::query()->where('article', 'A-3')->value('label'));
    }

    public function test_an_option_is_found_by_its_value_in_another_case_when_the_label_is_translated(): void
    {
        app()->setLocale('ru');
        $import = $this->import(['article', 'name', 'colors']);
        $this->importer($import)->import(2, ['A-1', 'Parka', 'Black|SILVER|Розовый']);

        $this->assertSame([], $import->errors);
        $this->assertSame(['black', 'silver', 'pink'], Product::query()->where('article', 'A-1')->first()->colors);
    }

    public function test_an_option_that_is_not_there_fails(): void
    {
        $import = $this->import(['article', 'name', 'label', 'colors']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', 'Cheap', '']);
        $importer->import(3, ['A-2', 'Boots', '', 'black|Green']);

        $this->assertSame(['"Label": there is no option "Cheap".'], $import->errors[0]['messages']);
        $this->assertSame(['"Colours": there is no option "Green".'], $import->errors[1]['messages']);
    }

    public function test_an_option_named_twice_fails_unless_the_value_says_which(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields, string $model): array => $model === Product::class ? [
            ...$fields,
            SelectField::make('package')->label('Package')->options(['1' => 'Box', '2' => 'box', '3' => '1'])->tab('params'),
            MultiSelectField::make('colors')->label('Colours')->options(['red' => 'Red', 'crimson' => 'red'])->tab('params'),
        ] : $fields);

        $import = $this->import(['article', 'name', 'package', 'colors']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', 'Box', '']);
        $importer->import(3, ['A-2', 'Boots', '', 'Red']);
        $importer->import(4, ['A-3', 'Hat', '1', 'crimson']);

        $this->assertSame(['"Package": more than one option is called "Box".'], $import->errors[0]['messages']);
        $this->assertSame(['"Colours": more than one option is called "Red".'], $import->errors[1]['messages']);
        $this->assertSame('1', Product::query()->where('article', 'A-3')->value('package'));
        $this->assertSame(['crimson'], Product::query()->where('article', 'A-3')->sole()->colors);
    }

    public function test_a_brand_is_found_by_its_name(): void
    {
        $brand = Brand::query()->create(['name' => 'Acme', 'code' => 'acme', 'active' => true]);

        $import = $this->import(['article', 'name', 'brand_id']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', (string) $brand->id]);
        $importer->import(3, ['A-2', 'Boots', 'Nobody']);

        $this->assertSame($brand->id, (int) Product::query()->where('article', 'A-1')->value('brand_id'));
        $this->assertSame(['"Brand": there is no option "Nobody".'], $import->errors[0]['messages']);
    }

    public function test_the_options_of_a_field_are_read_once_per_import(): void
    {
        Brand::query()->create(['name' => 'Acme', 'code' => 'acme', 'active' => true]);
        $import = $this->import(['article', 'name', 'brand_id']);
        $importer = $this->importer($import);
        $lists = static function (Closure $import): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $import();

            return count(array_filter(DB::getQueryLog(), static fn (array $query): bool => $query['query'] === 'select * from "brands"'));
        };

        $first = $lists(static fn () => $importer->import(2, ['A-1', 'Parka', 'Acme']));
        $second = $lists(static fn () => $importer->import(3, ['A-2', 'Boots', 'acme']));

        $this->assertSame($first - 1, $second);
        $this->assertSame(2, Product::query()->whereNotNull('brand_id')->count());
    }

    public function test_dates_are_read_day_first_and_a_date_drops_the_time(): void
    {
        $import = $this->import(['article', 'name', 'active_from', 'released_on']);
        $this->importer($import)->import(2, ['A-1', 'Parka', '05/03/2026 10:30', '05/03/2026 10:30']);

        $this->assertSame([], $import->errors);
        $this->assertSame(
            ['active_from' => '2026-03-05 10:30:00', 'released_on' => '2026-03-05 00:00:00'],
            (array) DB::table('catalog_products')->select('active_from', 'released_on')->first(),
        );
    }

    public function test_a_section_that_is_not_there_fails_unless_it_is_created(): void
    {
        $import = $this->import(['article', 'name', 'section']);
        $this->importer($import)->import(2, ['A-1', 'Parka', 'Одежда / Куртки']);
        $this->assertSame(['There is no section "Одежда / Куртки".'], $import->errors[0]['messages']);

        $import = $this->import(['article', 'name', 'section'], ['create_sections' => true]);
        $this->importer($import)->import(2, ['A-1', 'Parka', 'Одежда / Куртки']);

        $this->assertSame(2, $import->sections);
        $this->assertSame('Куртки', CatalogSection::query()->find(Product::query()->sole()->section_id)?->name);
    }

    public function test_an_image_that_is_not_there_fails_the_row(): void
    {
        $import = $this->import(['article', 'name', 'preview_picture', 'photos']);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka', 'catalog/none.jpg', '']);
        $importer->import(3, ['A-2', 'Boots', '', 'catalog/none.jpg']);

        $this->assertSame(['There is no image catalog/none.jpg on the site.'], $import->errors[0]['messages']);
        $this->assertSame(['There is no image catalog/none.jpg on the site.'], $import->errors[1]['messages']);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_an_image_url_is_downloaded_on_import(): void
    {
        Http::fake(['example.com/*' => Http::response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'))]);

        $import = $this->import(['article', 'name', 'preview_picture']);
        (new RowImporter($import, app(Targets::class), new Hosts(static fn (): array => ['93.184.216.34'])))->import(2, ['A-1', 'Parka', 'https://example.com/1.gif']);

        $this->assertStringStartsWith('/storage/catalog/', Product::query()->sole()->preview_picture);
    }

    public function test_a_check_writes_nothing_and_counts_what_the_import_would_do(): void
    {
        $this->product(['article' => 'A-1', 'name' => 'Parka', 'price' => 100]);

        $import = $this->import(['article', 'name', 'price', 'section', 'preview_picture'], ['create_sections' => true], CatalogImport::CHECKING);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', '', '150', '', '']);
        $importer->import(3, ['A-2', 'Boots', '90', 'Обувь', 'https://example.com/1.gif']);
        $importer->import(4, ['a-2', 'Boots', '95', '', '']);
        $importer->import(5, ['A-3', '', '10', '', '']);
        $importer->import(6, ['A-3', 'Hat', '10', '', '']);

        $this->assertSame([5, 2, 2, 1, 0], [$import->processed, $import->created, $import->updated, $import->failed, $import->skipped]);
        $this->assertSame([['row' => 5, 'skipped' => false, 'messages' => ['The name field is required.']]], $import->errors);
        $this->assertSame(1, $import->sections);
        $this->assertSame('100.00', Product::query()->sole()->price);
        $this->assertSame(0, CatalogSection::query()->count());
        Http::assertNothingSent();
    }

    public function test_a_check_skips_what_the_mode_would_skip_further_down_the_file(): void
    {
        $import = $this->import(['article', 'name'], ['mode' => 'create'], CatalogImport::CHECKING);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Parka']);
        $importer->import(3, ['A-1', 'Parka again']);

        $this->assertSame([1, 1], [$import->created, $import->skipped]);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_check_finds_the_reasons_the_form_would_give(): void
    {
        $this->product(['article' => 'A-1', 'name' => 'Parka']);

        $import = $this->import(['article', 'name', 'price'], [], CatalogImport::CHECKING);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', '', 'cheap']);
        $importer->import(3, ['A-2', 'Boots', '']);

        $this->assertSame(['The price field must be a number.'], $import->errors[0]['messages']);
        $this->assertSame([0, 1, 1], [$import->updated, $import->created, $import->failed]);
    }

    public function test_a_field_kept_per_language_takes_its_language_from_the_column(): void
    {
        Language::query()->create(['code' => 'ru', 'name' => 'Русский', 'active' => true, 'is_default' => false, 'sort' => 2]);
        app(ContentLanguages::class)->flush();
        Hook::filter('atrium.module.translatable', static fn (array $names, string $model): array => $model === Product::class ? [...$names, 'name'] : $names);

        $import = $this->import(['article', 'name.ru'], [], CatalogImport::CHECKING);
        $importer = $this->importer($import);
        $importer->import(2, ['A-1', 'Парка']);

        $import->mapping = ['article', 'name.en', 'name.ru'];
        $importer->import(3, ['A-2', 'Parka', 'Парка']);

        $this->assertSame(['The name (English) field is required.'], $import->errors[0]['messages']);
        $this->assertSame([1, 1], [$import->failed, $import->created]);
    }

    /**
     * @param  array<int,string|null>  $mapping
     * @param  array<string,mixed>  $options
     */
    private function import(array $mapping, array $options = [], string $status = CatalogImport::IMPORTING): CatalogImport
    {
        $import = CatalogImport::query()->create([
            'status' => CatalogImport::NEW, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => array_map(strval(...), array_keys($mapping)), 'total' => 1,
            'mapping' => $mapping,
            'options' => [...CatalogImport::DEFAULTS, ...$options],
        ]);
        $import->begin($status);

        return $import;
    }

    private function importer(CatalogImport $import): RowImporter
    {
        return new RowImporter($import, app(Targets::class), new Hosts(static fn (): array => ['10.0.0.1']));
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function product(array $attributes): Product
    {
        static $number = 0;
        $number++;

        return Product::query()->create(['code' => 'product-'.$number, 'active' => true, ...$attributes]);
    }
}
