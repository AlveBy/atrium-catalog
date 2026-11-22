<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Fields\Field;
use Atrium\Admin\Transfer\Target;
use Atrium\Admin\Transfer\Targets;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\ProductImport;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * The headers price lists use, in Russian and in English, find the fields
 * they stand for.
 */
final class ProductImportSynonymsTest extends ProductTransferTestCase
{
    /**
     * Every synonym the catalog knows, as a price list writes it.
     *
     * @return array<string,array{string,string}>
     */
    public static function headers(): array
    {
        $headers = [
            'name' => ['product', 'Product name', 'Товар', 'Название товара'],
            'article' => ['SKU', 'Vendor code', 'Part number', 'MPN', 'Артикул'],
            'price' => ['Cost', 'Цена', 'Стоимость'],
            'external_id' => ['XML ID', 'External code', 'GUID', 'Внешний код', 'Внешний ID', 'Код 1С'],
            'section_id' => ['Section', 'Category', 'Categories', 'Group', 'Раздел', 'Категория', 'Группа'],
            'photos' => ['Images', 'Gallery', 'Галерея', 'Фотографии'],
        ];
        $cases = [];

        foreach ($headers as $key => $names) {
            foreach ($names as $name) {
                $cases[$key.': '.$name] = [$name, $key];
            }
        }

        return $cases;
    }

    #[DataProvider('headers')]
    public function test_a_header_maps_to_the_field_it_stands_for(string $header, string $key): void
    {
        $this->assertSame([$key], Targets::for(Product::class)->guess([$header]));
    }

    public function test_the_list_above_is_every_synonym_there_is(): void
    {
        $synonyms = (new ReflectionClass(ProductImport::class))->getReflectionConstant('SYNONYMS')?->getValue();

        $this->assertSame(['name', 'article', 'price', 'external_id', 'section_id', 'photos'], array_keys($synonyms));
        $this->assertSame(
            [4, 5, 3, 6, 7, 4],
            array_map('count', array_values($synonyms)),
        );

        $normalized = [];

        foreach (self::headers() as [$header, $key]) {
            $normalized[$key][] = (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($header));
        }

        foreach ($synonyms as $key => $list) {
            $this->assertSame([], array_diff($list, $normalized[$key]), $key);
        }
    }

    public function test_a_whole_header_row_of_a_price_list_is_mapped_at_once(): void
    {
        $this->assertSame(
            ['article', 'name', 'price', 'external_id', 'section_id', 'photos', null],
            Targets::for(Product::class)->guess(['SKU', 'Product', 'Cost', 'GUID', 'Category', 'Images', 'Unknown column']),
        );
    }

    public function test_the_synonyms_add_to_the_labels_and_the_names_the_admin_knows(): void
    {
        $this->assertSame(
            ['name', 'code', 'article', 'price', 'sort', 'section_id', 'active'],
            Targets::for(Product::class)->guess(['Title', 'Slug', 'Article number', 'Price', 'order', 'Section', 'Enabled']),
        );
    }

    public function test_a_synonym_fills_one_column_only(): void
    {
        $this->assertSame(['article', null], Targets::for(Product::class)->guess(['SKU', 'Vendor code']));
    }

    public function test_the_synonyms_are_the_products_only(): void
    {
        $targets = ['name' => new Target('name', 'Name'), 'article' => new Target('article', 'Article')];

        $this->assertSame([], Hook::apply('atrium.import.targets', [], Brand::class));
        $this->assertSame($targets, Hook::apply('atrium.import.targets', $targets, Brand::class));
        $this->assertSame([], Targets::for(Brand::class)->get('name')?->synonyms);
        $this->assertSame(['product', 'productname', 'товар', 'названиетовара'], Hook::apply('atrium.import.targets', $targets, Product::class)['name']->synonyms);
        $this->assertSame([], $targets['name']->synonyms, 'The targets that came in are not changed.');
    }

    public function test_a_field_taken_out_of_the_form_leaves_its_synonyms_and_the_rest_stays(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields, string $model): array => $model === Product::class
            ? array_values(array_filter($fields, static fn (Field $field): bool => ! in_array($field->getName(), ['external_id', 'section_id'], true)))
            : $fields);

        $targets = Targets::for(Product::class);

        $this->assertNull($targets->get('external_id'));
        $this->assertNull($targets->get('section_id'));
        $this->assertSame([null, null, 'article', 'price'], $targets->guess(['GUID', 'Category', 'SKU', 'Cost']));
    }
}
