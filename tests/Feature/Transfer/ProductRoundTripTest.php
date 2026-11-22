<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the export writes goes back in as it is, and finds its products again.
 */
final class ProductRoundTripTest extends ProductTransferTestCase
{
    /**
     * @return array<string,array{string}>
     */
    public static function formats(): array
    {
        return ['csv' => ['csv'], 'xlsx' => ['xlsx']];
    }

    private function shop(): void
    {
        $acme = Brand::query()->create(['name' => 'Acme', 'code' => 'acme', 'active' => true]);
        $clothes = CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $jackets = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'parent_id' => $clothes->id, 'active' => true]);
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-clothes', 'parent_id' => $clothes->id, 'active' => true]);
        $shoes = CatalogSection::query()->create(['name' => 'Обувь', 'code' => 'shoes', 'active' => true]);
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-shoes', 'parent_id' => $shoes->id, 'active' => true]);
        Storage::disk('public')->put('catalog/1.jpg', 'x');
        Storage::disk('public')->put('catalog/2.jpg', 'x');

        Product::query()->create([
            'name' => 'Parka', 'code' => 'parka', 'article' => 'A-1', 'external_id' => 'ext-1', 'price' => 1299.5, 'sort' => 30, 'active' => true,
            'section_id' => $jackets->id, 'brand_id' => $acme->id, 'label' => 'hit', 'colors' => ['black', 'silver'], 'released_on' => '2026-03-05',
            'in_stock' => 'Y', 'features' => ['Warm', 'Light'], 'photos' => ['catalog/1.jpg', 'catalog/2.jpg'], 'preview_picture' => 'catalog/1.jpg',
            'package' => "Box;\nwith \"quotes\"", 'active_from' => '2026-01-02 08:30:00', 'active_to' => '2027-01-02 08:30:00',
            'preview_text' => 'Short', 'detail_text' => 'Long; text', 'specs' => '<p>Specs</p>',
        ]);
        Product::query()->create(['name' => 'Boots', 'code' => 'boots', 'article' => 'A-2', 'price' => 90, 'sort' => 10, 'active' => false, 'section_id' => CatalogSection::query()->where('code', 'kids-shoes')->value('id')]);
        Product::query()->create(['name' => '=SUM(1+1)', 'code' => 'formula', 'article' => '00123', 'price' => 5, 'sort' => 500, 'in_stock' => null]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function products(): array
    {
        return Product::query()->orderBy('id')->get()->map(static fn (Product $product): array => $product->makeHidden(['created_at', 'updated_at'])->toArray())->all();
    }

    private function export(string $format): string
    {
        $path = $this->files.'/export.'.$format;
        $this->artisan('atrium:export', ['module' => 'catalog_products', 'file' => $path])->assertExitCode(0);

        return $path;
    }

    #[DataProvider('formats')]
    public function test_an_exported_list_updates_the_same_products_without_changing_a_thing(string $format): void
    {
        $this->shop();
        $before = $this->products();
        $path = $this->export($format);

        $check = $this->importFile($path, ['--key' => 'id', '--mode' => 'update', '--check' => true]);
        $this->assertSame([3, 0, 3, 0, 0], [$check->processed, $check->created, $check->updated, $check->failed, $check->nodes], json_encode($check->errors));
        $this->assertSame($before, $this->products());

        $import = $this->importFile($path, ['--key' => 'id', '--mode' => 'update']);
        $this->assertSame([3, 0, 3, 0, 0], [$import->processed, $import->created, $import->updated, $import->failed, $import->nodes], json_encode($import->errors));
        $this->assertSame($before, $this->products());
    }

    #[DataProvider('formats')]
    public function test_the_products_are_found_again_by_every_key_the_file_has(string $format): void
    {
        $this->shop();
        $before = $this->products();
        $path = $this->export($format);

        foreach (['article', 'code', 'id'] as $key) {
            $import = $this->importFile($path, ['--key' => $key, '--mode' => 'update']);

            $this->assertSame([3, 0], [$import->updated, $import->failed + $import->created], $key.json_encode($import->errors));
            $this->assertSame($before, $this->products(), $key);
        }

        // Two of the three products have no external ID: their rows have no key.
        $import = $this->importFile($path, ['--key' => 'external_id', '--mode' => 'update']);

        $this->assertSame([1, 2, 0], [$import->updated, $import->failed, $import->created]);
        $this->assertSame(['row' => 2, 'skipped' => false, 'messages' => ['"External ID" is empty: the record cannot be found or created.']], $import->errors[0]);
        $this->assertSame($before, $this->products());
    }

    #[DataProvider('formats')]
    public function test_an_exported_list_rebuilds_the_products_that_are_gone(string $format): void
    {
        $this->shop();
        $before = $this->products();
        $path = $this->export($format);
        Product::query()->get()->each->delete();

        $import = $this->importFile($path, ['--key' => 'article']);

        $this->assertSame([3, 3, 0, 0, 0], [$import->processed, $import->created, $import->updated, $import->failed, $import->nodes], json_encode($import->errors));

        $after = $this->products();
        $this->assertCount(3, $after);

        // The same data; only the ids are new (the file lists the products by their sort).
        $byArticle = static function (array $products): array {
            $keyed = array_column(array_map(static fn (array $product): array => [...$product, 'id' => 0], $products), null, 'article');
            ksort($keyed);

            return $keyed;
        };
        $this->assertSame($byArticle($before), $byArticle($after));
        $this->assertNotSame(array_column($before, 'id'), array_column($after, 'id'));
    }

    public function test_a_list_exported_from_another_shop_makes_the_sections_it_names(): void
    {
        $this->shop();
        $path = $this->export('csv');
        Product::query()->get()->each->delete();
        CatalogSection::query()->get()->each->delete();

        $import = $this->importFile($path, ['--key' => 'article', '--option' => ['create_sections=1']]);

        $this->assertSame([3, 3, 0, 4], [$import->processed, $import->created, $import->failed, $import->nodes], json_encode($import->errors));
        $this->assertSame(
            ['=SUM(1+1)' => '', 'Boots' => 'Обувь / Детское', 'Parka' => 'Одежда / Куртки'],
            Product::query()->orderBy('id')->get()->mapWithKeys(fn (Product $product): array => [$product->name => $product->section_id === null ? '' : $this->path($product->section_id)])->sortKeys()->all(),
        );
        $this->assertSame(
            ['Детское', 'Куртки', 'Обувь', 'Одежда'],
            CatalogSection::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    private function path(int $id): string
    {
        $section = CatalogSection::query()->findOrFail($id);

        return $section->parent_id === null ? $section->name : $this->path($section->parent_id).' / '.$section->name;
    }
}
