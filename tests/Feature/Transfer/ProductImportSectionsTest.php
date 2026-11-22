<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Models\DataImport;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The section of a product is named by its name or its path in the file;
 * the sections that are not there are made when the import is told to, and
 * are an error when it is not.
 */
final class ProductImportSectionsTest extends ProductTransferTestCase
{
    private CatalogSection $clothes;

    private CatalogSection $jackets;

    private CatalogSection $shoes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clothes = CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $this->jackets = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'parent_id' => $this->clothes->id, 'active' => true]);
        $this->shoes = CatalogSection::query()->create(['name' => 'Обувь', 'code' => 'shoes', 'active' => true]);
        // A second "Детское" under each: the name alone does not say which.
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-clothes', 'parent_id' => $this->clothes->id, 'active' => true]);
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-shoes', 'parent_id' => $this->shoes->id, 'active' => true]);
    }

    /**
     * @param  array<int,string>  $sections  one product per section cell
     */
    private function sections(array $sections): string
    {
        $rows = [['Article', 'Name', 'Section']];

        foreach ($sections as $index => $section) {
            $rows[] = ['A-'.($index + 1), 'Product '.($index + 1), $section];
        }

        return $this->table('csv', $rows);
    }

    /**
     * What is in the tree: name, parent's name, code, active — in the order the sections were made.
     *
     * @return array<int,array{string,string|null,string,bool}>
     */
    private function tree(): array
    {
        $names = CatalogSection::query()->pluck('name', 'id');

        return CatalogSection::query()->orderBy('id')->get()
            ->map(static fn (CatalogSection $section): array => [$section->name, $section->parent_id === null ? null : $names[$section->parent_id], $section->code, (bool) $section->active])
            ->all();
    }

    public function test_a_name_anywhere_in_the_tree_and_a_path_from_the_top_find_the_section(): void
    {
        $kids = CatalogSection::query()->where('code', 'kids-shoes')->value('id');
        $path = $this->sections(['куртки', ' Обувь ', 'Одежда / Куртки', 'одежда/куртки/', 'Обувь / Детское', '/ Одежда']);

        $import = $this->importFile($path, ['--key' => 'article']);

        $this->assertSame([6, 6, 0, 0, 0], [$import->processed, $import->created, $import->failed, $import->skipped, $import->nodes]);
        $this->assertSame(
            [$this->jackets->id, $this->shoes->id, $this->jackets->id, $this->jackets->id, $kids, $this->clothes->id],
            Product::query()->orderBy('article')->pluck('section_id')->all(),
        );
        $this->assertCount(5, CatalogSection::query()->get());
    }

    public function test_the_separator_can_be_another_one(): void
    {
        $import = $this->importFile($this->sections(['Одежда > Куртки']), ['--key' => 'article', '--option' => ['separator=>']]);

        $this->assertSame([1, 0], [$import->created, $import->failed]);
        $this->assertSame($this->jackets->id, Product::query()->sole()->section_id);
        $this->assertSame('>', $import->option('separator'));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function missing(): array
    {
        return [
            'a name that is not there' => ['Шапки', '"Section": there is no "Шапки".'],
            'a path that is not there' => ['Одежда / Шапки', '"Section": there is no "Одежда / Шапки".'],
            'a name under the wrong parent' => ['Обувь / Куртки', '"Section": there is no "Обувь / Куртки".'],
            'separators only' => [' / ', '"Section": there is no "/".'],
            'two sections of that name' => ['Детское', '"Section": more than one is called "Детское": write its path, "Parent / Детское".'],
        ];
    }

    #[DataProvider('missing')]
    public function test_without_the_option_a_section_that_cannot_be_found_fails_the_row_and_creates_nothing(string $cell, string $message): void
    {
        $import = $this->importFile($this->sections([$cell]), ['--key' => 'article']);

        $this->assertSame([1, 0, 1, 0], [$import->processed, $import->created, $import->failed, $import->nodes]);
        $this->assertSame([['row' => 2, 'skipped' => false, 'messages' => [$message]]], $import->errors);
        $this->assertSame(0, Product::query()->count());
        $this->assertCount(5, CatalogSection::query()->get());
    }

    public function test_the_option_set_to_zero_is_the_same_as_without_it(): void
    {
        $import = $this->importFile($this->sections(['Шапки']), ['--key' => 'article', '--option' => ['create_sections=0']]);

        $this->assertSame([0, 1, 0], [$import->created, $import->failed, $import->nodes]);
        $this->assertFalse($import->option('create_sections'));
        $this->assertCount(5, CatalogSection::query()->get());
    }

    public function test_with_the_option_the_missing_sections_are_made_with_the_path_to_them(): void
    {
        $path = $this->sections([
            'Одежда / Шапки / Зимние',
            'Одежда / Шапки / Зимние',
            'Носки',
            // A code already taken gets a number.
            'Обувь / Зимние',
            'куртки',
        ]);

        $import = $this->importFile($path, ['--key' => 'article', '--option' => ['create_sections']]);

        $this->assertTrue($import->option('create_sections'));
        $this->assertSame([5, 5, 0, 4], [$import->processed, $import->created, $import->failed, $import->nodes]);
        $this->assertSame([
            ['Шапки', 'Одежда', 'shapki', true],
            ['Зимние', 'Шапки', 'zimnie', true],
            ['Носки', null, 'noski', true],
            ['Зимние', 'Обувь', 'zimnie-1', true],
        ], array_slice($this->tree(), 5));
        $this->assertCount(9, CatalogSection::query()->get());

        $ids = CatalogSection::query()->pluck('id', 'code');
        $this->assertSame(
            [$ids['zimnie'], $ids['zimnie'], $ids['noski'], $ids['zimnie-1'], $this->jackets->id],
            Product::query()->orderBy('article')->pluck('section_id')->all(),
        );
    }

    public function test_a_path_that_is_partly_there_is_made_under_the_part_that_is_there(): void
    {
        $import = $this->importFile($this->sections(['Обувь / Куртки', 'Одежда / Куртки / Пуховые']), ['--key' => 'article', '--option' => ['create_sections=1']]);

        $this->assertSame([2, 2], [$import->created, $import->nodes]);
        $this->assertSame([
            ['Куртки', 'Обувь', 'kurtki', true],
            ['Пуховые', 'Куртки', 'puhovye', true],
        ], array_slice($this->tree(), 5));
        $this->assertSame($this->jackets->id, CatalogSection::query()->where('code', 'puhovye')->sole()->parent_id);
    }

    public function test_the_option_does_not_make_what_the_file_does_not_name_clearly(): void
    {
        $import = $this->importFile($this->sections(['Детское', ' / ']), ['--key' => 'article', '--option' => ['create_sections=1']]);

        $this->assertSame([0, 2, 0], [$import->created, $import->failed, $import->nodes]);
        $this->assertSame([
            ['row' => 2, 'skipped' => false, 'messages' => ['"Section": more than one is called "Детское": write its path, "Parent / Детское".']],
            ['row' => 3, 'skipped' => false, 'messages' => ['"Section": there is no "/".']],
        ], $import->errors);
        $this->assertCount(5, CatalogSection::query()->get());
    }

    public function test_a_section_the_file_names_for_a_known_product_is_made_too(): void
    {
        $product = Product::query()->create(['article' => 'A-1', 'name' => 'Parka']);

        $import = $this->importFile($this->sections(['Шапки']), ['--key' => 'article', '--option' => ['create_sections=1']]);

        $this->assertSame([0, 1, 1], [$import->created, $import->updated, $import->nodes]);
        $this->assertSame(CatalogSection::query()->where('code', 'shapki')->sole()->id, $product->refresh()->section_id);
    }

    public function test_a_check_counts_the_sections_it_would_make_once_and_makes_none(): void
    {
        $path = $this->sections([
            'Одежда / Шапки / Зимние',
            'одежда / шапки / зимние',
            'Одежда / Шапки',
            'Носки',
            'Куртки',
            'Одежда / Зимние',
            'Обувь / Зимние',
        ]);

        $import = $this->importFile($path, ['--key' => 'article', '--check' => true, '--option' => ['create_sections=1']]);

        $this->assertSame(DataImport::CHECKED, $import->status);
        $this->assertSame([7, 7, 0, 5], [$import->processed, $import->created, $import->failed, $import->nodes]);
        $this->assertCount(5, CatalogSection::query()->get());
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_check_without_the_option_reports_the_missing_section_and_makes_none(): void
    {
        $import = $this->importFile($this->sections(['Шапки', 'Куртки']), ['--key' => 'article', '--check' => true]);

        $this->assertSame([1, 1, 0], [$import->created, $import->failed, $import->nodes]);
        $this->assertSame([['row' => 2, 'skipped' => false, 'messages' => ['"Section": there is no "Шапки".']]], $import->errors);
        $this->assertCount(5, CatalogSection::query()->get());
    }

    public function test_an_option_the_module_does_not_have_is_refused(): void
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $this->sections(['Куртки']), '--key' => 'article', '--option' => ['make_everything=1']])
            ->expectsOutput('The module has no option "make_everything" for --option.')
            ->assertExitCode(1);

        $this->assertSame(0, DataImport::query()->count());
    }

    public function test_the_option_takes_yes_or_no_only(): void
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $this->sections(['Куртки']), '--key' => 'article', '--option' => ['create_sections=maybe']])
            ->expectsOutput('The Create the sections that are not there field must be true or false.')
            ->assertExitCode(1);

        $this->assertSame(0, DataImport::query()->count());
    }

    public function test_the_tree_is_read_once_for_the_whole_file_and_not_for_every_row(): void
    {
        $reads = function (array $cells): int {
            $count = 0;
            DB::listen(static function ($query) use (&$count): void {
                $count += str_contains($query->sql, 'catalog_sections') && str_contains($query->sql, 'order by') ? 1 : 0;
            });
            $this->importFile($this->sections($cells), ['--key' => 'article', '--check' => true]);

            return $count;
        };

        $one = $reads(['Куртки']);
        $many = $reads(['Куртки', 'Обувь', 'Одежда / Куртки', 'Одежда', 'Куртки']);

        $this->assertGreaterThan(0, $one);
        $this->assertSame($one, $many);
    }
}
