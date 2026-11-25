<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The list of the products as a file: from the console and from the admin.
 */
final class ProductExportTest extends ProductTransferTestCase
{
    /** A cell that would be a formula in a spreadsheet is written with an apostrophe in front, in CSV. */
    private const FORMULA = "'=SUM(1+1)";

    private const HEADER = [
        'ID', 'Active', 'Publish at', 'Unpublish at', 'Active from', 'Active until', 'Name', 'Code', 'Sort', 'Article number', 'External ID', 'Price', 'Brand', 'Label',
        'Colours', 'Released', 'In stock', 'Features', 'Preview picture', 'Preview text', 'Detail picture', 'Detail text', 'Specifications',
        'In the box', 'Photos', 'Section',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $clothes = CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $jackets = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'parent_id' => $clothes->id, 'active' => true]);

        Product::query()->create([
            'name' => 'Parka', 'code' => 'parka', 'article' => 'A-1', 'external_id' => 'ext-1', 'price' => 1299.5, 'sort' => 30, 'active' => true,
            'section_id' => $jackets->id, 'label' => 'hit', 'colors' => ['black', 'silver'], 'released_on' => '2026-03-05', 'in_stock' => 'Y',
            'features' => ['Warm', 'Light'], 'photos' => ['catalog/1.jpg', 'catalog/2.jpg'], 'preview_picture' => 'catalog/1.jpg', 'package' => 'Box',
            'active_from' => '2026-01-02 08:30:00',
        ]);
        Product::query()->create(['name' => 'Boots', 'code' => 'boots', 'article' => 'A-2', 'price' => 90, 'sort' => 10, 'active' => false, 'section_id' => $clothes->id]);
        Product::query()->create(['name' => '=SUM(1+1)', 'code' => 'formula', 'article' => 'A-3', 'price' => 5, 'sort' => 20]);
    }

    public function test_the_console_writes_a_header_and_a_row_for_each_product(): void
    {
        $path = $this->files.'/out.csv';

        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $path])
            ->expectsOutput("Records written: 3, to {$path}.")
            ->assertExitCode(0);

        $rows = $this->read($path);

        $this->assertSame(self::HEADER, array_slice(array_keys($rows[0]), 0, 26));
        // The module's own columns and the Template the theme offers every page.
        $this->assertSame(39, count($rows[0]));
        // The default order of the list: by sort.
        $this->assertSame(['Boots', self::FORMULA, 'Parka'], array_column($rows, 'Name'));
        $this->assertSame([
            'ID' => '1', 'Active' => '1', 'Publish at' => '', 'Unpublish at' => '', 'Active from' => '2026-01-02 08:30:00', 'Active until' => '', 'Name' => 'Parka', 'Code' => 'parka', 'Sort' => '30',
            'Article number' => 'A-1', 'External ID' => 'ext-1', 'Price' => '1299.50', 'Brand' => '', 'Label' => 'hit', 'Colours' => 'black|silver',
            'Released' => '2026-03-05', 'In stock' => '1', 'Features' => 'Warm|Light', 'Preview picture' => 'catalog/1.jpg', 'Preview text' => '',
            'Detail picture' => '', 'Detail text' => '', 'Specifications' => '', 'In the box' => 'Box', 'Photos' => 'catalog/1.jpg|catalog/2.jpg',
            'Section' => 'Одежда / Куртки',
        ], array_slice($rows[2], 0, 26, true));
        $this->assertSame(['2', '0', 'Boots', '90.00', 'Одежда'], [$rows[0]['ID'], $rows[0]['Active'], $rows[0]['Name'], $rows[0]['Price'], $rows[0]['Section']]);
        $this->assertSame('', $rows[1]['Section']);
    }

    public function test_the_default_order_is_the_lists_and_ties_go_by_id(): void
    {
        Product::query()->create(['name' => 'Twin', 'code' => 'twin', 'article' => 'A-4', 'sort' => 30]);

        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $path = $this->files.'/out.csv'])->assertExitCode(0);

        $this->assertSame(['Boots', self::FORMULA, 'Parka', 'Twin'], array_column($this->read($path), 'Name'));
    }

    /**
     * @return array<string,array{array<string,string>,array<int,string>,int}>
     */
    public static function narrowed(): array
    {
        return [
            'by the search' => [['--search' => 'boot'], ['Boots'], 1],
            'by a search that finds nothing' => [['--search' => 'sandals'], [], 0],
            'by the price, up' => [['--sort' => 'price'], [self::FORMULA, 'Boots', 'Parka'], 3],
            'by the price, down' => [['--sort' => 'price', '--direction' => 'desc'], ['Parka', 'Boots', self::FORMULA], 3],
            'the search and the sort' => [['--search' => 'a', '--sort' => 'name', '--direction' => 'desc'], ['Parka'], 1],
            'a filter the module does not have' => [['--filter' => ['section_id=1']], ['Boots', self::FORMULA, 'Parka'], 3],
        ];
    }

    /**
     * @param  array<string,mixed>  $options
     * @param  array<int,string>  $names
     */
    #[DataProvider('narrowed')]
    public function test_the_list_is_narrowed_as_the_screen_narrows_it(array $options, array $names, int $count): void
    {
        $path = $this->files.'/out.csv';

        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $path, ...$options])
            ->expectsOutput("Records written: {$count}, to {$path}.")
            ->assertExitCode(0);

        $this->assertSame($names, array_column($this->read($path), 'Name'));
    }

    public function test_the_extension_names_the_format_and_the_option_overrides_it(): void
    {
        $xlsx = $this->files.'/out.xlsx';
        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $xlsx])->assertExitCode(0);
        $this->assertStringStartsWith('PK', (string) file_get_contents($xlsx));
        $this->assertSame(['Boots', '=SUM(1+1)', 'Parka'], array_column($this->read($xlsx), 'Name'));

        $named = $this->files.'/out.dat';
        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $named, '--format' => 'xlsx'])->assertExitCode(0);
        $this->assertStringStartsWith('PK', (string) file_get_contents($named));

        $csv = $this->files.'/out.CSV';
        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $csv])->assertExitCode(0);
        $this->assertStringStartsWith("\xEF\xBB\xBFID;", (string) file_get_contents($csv));
    }

    public function test_a_format_that_is_none_is_refused_and_nothing_is_written(): void
    {
        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $path = $this->files.'/out.txt'])
            ->expectsOutput('The format must be one of: csv, xlsx.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($path);
    }

    public function test_a_module_that_is_not_there_is_refused(): void
    {
        $this->artisan('atrium:export', ['module' => 'nothing', 'file' => $this->files.'/out.csv'])
            ->expectsOutput('No module "nothing" can be exported. The ones that can: sites, languages, catalog_products, catalog_sections, brands, news, stores.')
            ->assertExitCode(1);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function formats(): array
    {
        return ['csv' => ['csv'], 'xlsx' => ['xlsx']];
    }

    #[DataProvider('formats')]
    public function test_the_admin_downloads_the_list_as_a_file_of_the_day(string $format): void
    {
        $this->travelTo('2026-11-22 10:00:00');
        $this->actingAs($this->admin(), 'admin');

        $response = $this->get("/admin/catalog_products/export?format={$format}&search=parka");

        $response->assertOk();
        $this->assertStringContainsString('catalog-products-2026-11-22.'.$format, (string) $response->headers->get('content-disposition'));
        $copy = $this->files.'/download.'.$format;
        copy($response->baseResponse->getFile()->getPathname(), $copy);
        $this->assertSame(['Parka'], array_column($this->read($copy), 'Name'));
    }

    public function test_the_admin_downloads_csv_unless_told_otherwise_in_the_order_of_the_query(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $response = $this->get('/admin/catalog_products/export?sort=price&direction=desc');

        $response->assertOk();
        $this->assertStringContainsString('.csv', (string) $response->headers->get('content-disposition'));
        $copy = $this->files.'/download.csv';
        copy($response->baseResponse->getFile()->getPathname(), $copy);
        $this->assertSame(['Parka', 'Boots', self::FORMULA], array_column($this->read($copy), 'Name'));
    }

    public function test_a_format_the_admin_does_not_know_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get('/admin/catalog_products/export?format=pdf')->assertStatus(422);
    }
}
