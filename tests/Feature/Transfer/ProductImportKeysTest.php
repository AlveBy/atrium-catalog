<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Models\DataImport;
use Atrium\Admin\Transfer\Settings;
use Atrium\Admin\Transfer\Targets;
use Atrium\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A price list knows a product by its article first, then by its code, the
 * code of the accounting program or the id.
 */
final class ProductImportKeysTest extends ProductTransferTestCase
{
    public function test_the_products_name_their_keys_in_order(): void
    {
        $this->assertSame(['article', 'code', 'external_id', 'id'], Product::getImportKeys());
        $this->assertSame([
            ['key' => 'article', 'label' => 'Article number'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'external_id', 'label' => 'External ID'],
            ['key' => 'id', 'label' => 'ID'],
        ], Targets::for(Product::class)->keys());
    }

    /**
     * @return array<string,array{array<int,string|null>,string}>
     */
    public static function mappings(): array
    {
        return [
            'article first of all' => [['article', 'code', 'external_id', 'id'], 'article'],
            'the article wherever its column is' => [['external_id', 'name', 'article'], 'article'],
            'the code before the external id' => [['external_id', 'code', 'price'], 'code'],
            'the external id before the id' => [['id', 'external_id'], 'external_id'],
            'the id when it is all there is' => [['id', 'name'], 'id'],
            'the article when no key is there' => [['name', 'price', null], 'article'],
        ];
    }

    /**
     * @param  array<int,string|null>  $mapping
     */
    #[DataProvider('mappings')]
    public function test_the_key_is_the_first_one_a_column_fills(array $mapping, string $key): void
    {
        $this->assertSame($key, Settings::key($mapping, Targets::for(Product::class)));
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function keys(): array
    {
        return [
            'article' => ['article', 'Article number', 'A-1'],
            'code' => ['code', 'Code', 'parka'],
            'external id' => ['external_id', 'External ID', 'ext-1'],
            'id' => ['id', 'ID', '{id}'],
        ];
    }

    #[DataProvider('keys')]
    public function test_products_are_found_by_the_key_the_import_names(string $key, string $header, string $cell): void
    {
        $parka = Product::query()->create(['article' => 'A-1', 'code' => 'parka', 'external_id' => 'ext-1', 'name' => 'Parka', 'price' => 100]);
        $boots = Product::query()->create(['article' => 'A-2', 'code' => 'boots', 'external_id' => 'ext-2', 'name' => 'Boots', 'price' => 90]);
        $new = $key === 'id' ? '99' : 'new';
        $path = $this->file('price.csv', "{$header};Name;Price\n".str_replace('{id}', (string) $parka->id, $cell).";Parka;150\n{$new};Hat;7\n");

        $import = $this->importFile($path, ['--key' => $key, '--map' => ["{$header}={$key}"]]);

        $this->assertSame($key, $import->option('key'));
        $this->assertSame([2, 1, 1, 0], [$import->processed, $import->updated, $import->created, $import->failed]);
        $this->assertSame('150.00', $parka->refresh()->price);
        $this->assertSame('90.00', $boots->refresh()->price);
        $this->assertSame(3, Product::query()->count());
    }

    /**
     * @return array<string,array{string,array<int,string>}>
     */
    public static function winners(): array
    {
        return [
            'the article' => ['article', ['50.00', '2.00']],
            'the external id' => ['external_id', ['1.00', '50.00']],
        ];
    }

    /**
     * @param  array<int,string>  $prices
     */
    #[DataProvider('winners')]
    public function test_the_key_the_import_names_wins_over_the_other_columns(string $key, array $prices): void
    {
        $first = Product::query()->create(['article' => 'A-1', 'external_id' => 'ext-1', 'name' => 'First', 'price' => 1]);
        $second = Product::query()->create(['article' => 'A-2', 'external_id' => 'ext-2', 'name' => 'Second', 'price' => 2]);
        // The article says the first product, the external id the second.
        $path = $this->file('price.csv', "Article number;External ID;Price\nA-1;ext-2;50\n");

        $this->importFile($path, ['--key' => $key]);

        $this->assertSame($prices, [$first->refresh()->price, $second->refresh()->price]);
    }

    public function test_an_id_that_is_not_a_number_fails_the_row(): void
    {
        $path = $this->file('price.csv', "ID;Name\nabc;Parka\n0;Boots\n");

        $import = $this->importFile($path, ['--key' => 'id']);

        $this->assertSame([0, 2], [$import->created, $import->failed]);
        $this->assertSame([
            ['row' => 2, 'skipped' => false, 'messages' => ['"abc" is not an id: it must be a whole number above zero.']],
            ['row' => 3, 'skipped' => false, 'messages' => ['"0" is not an id: it must be a whole number above zero.']],
        ], $import->errors);
    }

    public function test_a_key_the_products_do_not_have_is_refused(): void
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $this->file('price.csv', "Name;Price\nParka;1\n"), '--key' => 'name'])
            ->expectsOutput('The selected Find records by is invalid.')
            ->assertExitCode(1);

        $this->assertSame(0, DataImport::query()->count());
    }

    public function test_a_key_that_no_column_fills_is_refused(): void
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $this->file('price.csv', "Name;Price\nParka;1\n"), '--key' => 'code'])
            ->expectsOutput('Records are found by "Code", but no column fills it.')
            ->assertExitCode(1);

        $this->assertSame(0, DataImport::query()->count());
        $this->assertSame(0, Product::query()->count());
    }
}
