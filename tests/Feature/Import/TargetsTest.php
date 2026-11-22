<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Catalog\Import\Targets;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;

/**
 * What a column can fill, and the guess of the mapping from the headers.
 */
final class TargetsTest extends TestCase
{
    public function test_every_field_a_cell_can_fill_and_the_section_by_name(): void
    {
        $keys = array_column(app(Targets::class)->options(), 'key');

        $this->assertSame(['active', 'active_from', 'active_to', 'name', 'code', 'sort', 'article', 'external_id', 'price', 'brand_id', 'label', 'colors', 'released_on', 'in_stock', 'features', 'preview_picture', 'preview_text', 'detail_picture', 'detail_text', 'specs', 'package', 'photos', 'section'], array_slice($keys, 0, 23));
        $this->assertContains('seo_title', $keys);

        // Links to many records, read-only fields, documents and the tree are left out.
        foreach (['stores', 'related', 'updated', 'manual', 'section_id'] as $key) {
            $this->assertNotContains($key, $keys);
        }

        $this->assertSame(['key' => 'section', 'label' => 'Section'], app(Targets::class)->options()[22]);
        $this->assertSame('Price', app(Targets::class)->get('price')['label']);
        $this->assertNull(app(Targets::class)->get('section')['field']);
        $this->assertNull(app(Targets::class)->get('stores'));
    }

    public function test_headers_are_guessed_from_labels_in_any_language_keys_and_synonyms(): void
    {
        app()->setLocale('de');

        $mapping = app(Targets::class)->guess([
            'Наименование',   // a synonym
            'Артикул',        // the Russian label
            'Price',          // the English label
            'Цена',           // the price again: taken already
            'released_on',    // the key itself
            'Категория',      // a synonym of the section
            'Something else',
            '',
        ]);

        $this->assertSame(['name', 'article', 'price', null, 'released_on', 'section', null, null], $mapping);
        $this->assertSame('de', app()->getLocale());
    }

    public function test_the_targets_are_worked_out_once(): void
    {
        $calls = 0;
        Hook::filter('atrium.module.fields', static function (array $fields) use (&$calls): array {
            $calls++;

            return $fields;
        });
        $targets = app(Targets::class);

        $targets->all();
        $targets->get('name');
        $targets->options();

        $this->assertSame(1, $calls);
    }

    public function test_what_comes_first_wins_a_header_two_targets_answer_to(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields): array => [
            ...$fields,
            TextField::make('alt_name')->label('Name'),   // a label after the same label
            TextField::make('legacy')->label('Article'),  // a label before the same key
            TextField::make('stock_code')->label('SKU'),  // a label before the same synonym
        ]);

        $this->assertSame(['name', 'legacy', 'stock_code'], app(Targets::class)->guess(['Name', 'Article', 'SKU']));
    }

    public function test_a_field_kept_per_language_takes_a_column_per_language(): void
    {
        Language::query()->create(['code' => 'ru', 'name' => 'Русский', 'active' => true, 'is_default' => false, 'sort' => 2]);
        app(ContentLanguages::class)->flush();
        Hook::filter('atrium.module.translatable', static fn (array $names, string $model): array => $model === Product::class ? [...$names, 'name'] : $names);

        $targets = app(Targets::class);

        $this->assertNull($targets->get('name'));
        $this->assertSame(['label' => 'Name (English)', 'base' => 'Name', 'locale' => 'en'], array_diff_key($targets->get('name.en'), ['field' => 0]));
        $this->assertSame('Name (Русский)', $targets->get('name.ru')['label']);
        $this->assertSame('name', $targets->get('name.ru')['field']?->getName());

        // "Name" alone is the default language's; the other one is named with its language.
        $this->assertSame(['name.en'], $targets->guess(['Name']));
        $this->assertSame(['name.en'], $targets->guess(['Название']));
        $this->assertSame(['name.ru'], $targets->guess(['Name (Русский)']));
        $this->assertSame(['name.ru'], $targets->guess(['Название (Русский)']));
        $this->assertSame([null], $targets->guess(['Название (Deutsch)']));
    }

    public function test_the_first_of_two_fields_kept_per_language_with_one_label_takes_the_bare_label(): void
    {
        Language::query()->create(['code' => 'ru', 'name' => 'Русский', 'active' => true, 'is_default' => false, 'sort' => 2]);
        app(ContentLanguages::class)->flush();
        Hook::filter('atrium.module.fields', static fn (array $fields): array => [...$fields, TextField::make('alt_name')->label('Name')]);
        Hook::filter('atrium.module.translatable', static fn (array $names, string $model): array => $model === Product::class ? [...$names, 'name', 'alt_name'] : $names);

        $this->assertSame(['name.en'], app(Targets::class)->guess(['Name']));
    }

    public function test_one_language_needs_no_language_in_the_name(): void
    {
        Hook::filter('atrium.module.translatable', static fn (array $names, string $model): array => $model === Product::class ? [...$names, 'name'] : $names);

        $this->assertSame('Name', app(Targets::class)->get('name')['label']);
        $this->assertNull(app(Targets::class)->get('name.en'));
    }

    public function test_a_synonym_of_a_field_the_form_does_not_have_maps_to_nothing(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields, string $model): array => array_values(array_filter(
            $fields,
            static fn ($field): bool => $field->getName() !== 'article',
        )));

        $this->assertSame([null, 'price'], app(Targets::class)->guess(['SKU', 'Cost']));
    }

    public function test_a_custom_field_is_a_target_too(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields, string $model): array => [...$fields, TextField::make('colour_code')->label('Colour code')]);

        $this->assertSame(['colour_code'], app(Targets::class)->guess(['Colour code']));
    }
}
