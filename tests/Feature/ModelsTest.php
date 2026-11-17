<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\Field;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Sections and products: how they hang together and what the panel shows
 * of them.
 */
final class ModelsTest extends TestCase
{
    public function test_a_section_knows_its_parent_and_its_products(): void
    {
        $phones = CatalogSection::query()->create(['name' => 'Phones', 'code' => 'phones']);
        $smart = CatalogSection::query()->create(['name' => 'Smartphones', 'code' => 'smart', 'parent_id' => $phones->id]);
        $pulse = Product::query()->create(['name' => 'Pulse', 'section_id' => $smart->id]);
        Product::query()->create(['name' => 'Other', 'section_id' => $phones->id]);

        $this->assertTrue($smart->parent?->is($phones));
        $this->assertNull($phones->parent);
        $this->assertSame([$pulse->id], $smart->products()->pluck('id')->all());
        $this->assertTrue($pulse->section?->is($smart));
        $this->assertNull(Product::query()->create(['name' => 'Loose'])->section);
    }

    public function test_the_relations_and_the_form_tabs_are_public_api(): void
    {
        // Model::__call hides a protected relation, so visibility is read as declared.
        foreach ([[CatalogSection::class, 'parent'], [Product::class, 'section'], [Product::class, 'getAdminTabs']] as [$class, $method]) {
            $this->assertTrue((new \ReflectionMethod($class, $method))->isPublic(), "{$class}::{$method}");
        }
    }

    public function test_the_label_and_colour_choices_of_a_product(): void
    {
        $fields = $this->byName(Product::getAdministrationFields());
        $values = fn (string $name): array => array_column($fields[$name]->toArray()['options'], 'value');

        $this->assertSame(['new', 'hit', 'sale'], $values('label'));
        $this->assertSame(['black', 'silver', 'blue', 'pink'], $values('colors'));
        $this->assertSame('New', $fields['label']->toArray()['options'][0]['label']);
        $this->assertSame('Black', $fields['colors']->toArray()['options'][0]['label']);
    }

    public function test_a_product_takes_ten_features_and_no_more(): void
    {
        $features = $this->byName(Product::getAdministrationFields())['features'];

        $this->assertSame(10, $features->toArray()['maxItems']);
        $this->assertContains('max:10', $features->getValidationRules());
    }

    public function test_the_active_flag_of_a_section_is_read_back_as_a_boolean(): void
    {
        $section = CatalogSection::query()->create(['name' => 'Phones', 'code' => 'phones', 'active' => 1]);

        $this->assertTrue($section->fresh()?->active);
    }

    public function test_the_section_form_and_list(): void
    {
        $fields = $this->byName(CatalogSection::getAdministrationFields());

        $this->assertSame(['active', 'name', 'code', 'parent_id', 'sort', 'picture', 'description'], array_keys($fields));
        $this->assertTrue($fields['active']->getDefault());
        $this->assertSame(500, $fields['sort']->getDefault());
        $this->assertSame(['id', 'name', 'sort', 'active'], array_map(fn ($column): string => $column->getName(), CatalogSection::getAdministrationListColumns()));
    }

    public function test_the_product_form_tabs_and_list(): void
    {
        $fields = $this->byName(Product::getAdministrationFields());

        $this->assertSame(['params', 'preview', 'detail', 'seo', 'sections'], array_column(Product::getAdminTabs(), 'name'));
        $this->assertSame('active', array_key_first($fields));
        $this->assertTrue($fields['active']->getDefault());
        $this->assertSame(500, $fields['sort']->getDefault());
        $this->assertSame('sections', $fields['section_id']->getTab());
        $this->assertSame(
            ['id', 'preview_picture', 'name', 'price', 'brand.name', 'sort', 'active'],
            array_map(fn ($column): string => $column->getName(), Product::getAdministrationListColumns()),
        );
    }

    public function test_the_form_shows_when_a_product_was_last_changed(): void
    {
        $field = $this->byName(Product::getAdministrationFields())['updated'];
        $this->assertInstanceOf(DisplayField::class, $field);

        $product = Product::query()->create(['name' => 'Pulse']);
        $product->updated_at = Carbon::parse('2026-03-04 05:06:00');

        $this->assertSame(Carbon::parse('2026-03-04 05:06:00')->isoFormat('LLL'), $field->render($product));

        $product->updated_at = null;
        $this->assertSame('', $field->render($product));

        // Another kind of record has nothing to show here, dated or not.
        $section = CatalogSection::query()->create(['name' => 'Phones', 'code' => 'phones']);
        $this->assertNotNull($section->updated_at);
        $this->assertSame('', $field->render($section));
    }

    /**
     * @return array<string,array{0:mixed,1:bool}>
     */
    public static function perPage(): array
    {
        return [
            'empty: the default' => ['', true],
            'one' => [1, true],
            'the most' => [100, true],
            'zero' => [0, false],
            'a fraction' => [1.5, false],
            'one too many' => [101, false],
            'negative' => [-5, false],
            'not a number' => ['many', false],
        ];
    }

    #[DataProvider('perPage')]
    public function test_products_per_page_may_be_left_empty_or_set_within_bounds(mixed $value, bool $valid): void
    {
        $response = $this->actingAs($this->admin(), 'admin')
            ->put('/admin/modules/catalog/settings', ['per_page' => $value]);

        $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('per_page');
    }

    /**
     * @param  array<int,Field>  $fields
     * @return array<string,Field>
     */
    private function byName(array $fields): array
    {
        return array_combine(array_map(fn (Field $field): string => $field->getName(), $fields), $fields);
    }
}
