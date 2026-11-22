<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\DataImport;
use Atrium\Admin\Transfer\Settings;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;

/**
 * What the import screens of the products show and do with what the catalog
 * adds: its option, its headers, its keys.
 */
final class ProductImportScreensTest extends ProductTransferTestCase
{
    private const SEPARATOR = ['name' => 'separator', 'label' => 'Path separator', 'type' => 'text', 'default' => '/', 'hint' => 'Separates a parent from its child in a path: "Clothes / Jackets".'];

    private const CREATE_SECTIONS = ['name' => 'create_sections', 'label' => 'Create the sections that are not there', 'type' => 'boolean', 'default' => false, 'hint' => ''];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin(), 'admin');
    }

    private function upload(string $module, string $content): DataImport
    {
        $this->post("/admin/{$module}/import", ['file' => UploadedFile::fake()->createWithContent('price.csv', $content)])->assertRedirect();

        return DataImport::query()->latest('id')->firstOrFail();
    }

    /**
     * Ask for the state of the import until the work is over.
     *
     * @return array<string,mixed>
     */
    private function advance(DataImport $import): array
    {
        $state = [];

        for ($step = 0; $step < 5; $step++) {
            $state = $this->postJson("/admin/catalog_products/import/{$import->id}/step")->assertOk()->json();

            if (! in_array($state['status'], [DataImport::CHECKING, DataImport::IMPORTING], true)) {
                break;
            }
        }

        return $state;
    }

    public function test_the_screen_offers_the_option_and_the_keys_of_the_catalog(): void
    {
        $import = $this->upload('catalog_products', "SKU;Product;Cost;GUID;Category;Images\nA-1;Parka;10;g-1;Куртки;catalog/1.jpg\n");

        $this->get("/admin/catalog_products/import/{$import->id}")
            ->assertOk()
            ->assertInertia(static fn (AssertableInertia $page) => $page
                ->component('Admin/Transfer/Import/Show')
                ->where('moduleOptions', [self::SEPARATOR, self::CREATE_SECTIONS])
                ->where('keys', [
                    ['key' => 'article', 'label' => 'Article number'],
                    ['key' => 'code', 'label' => 'Code'],
                    ['key' => 'external_id', 'label' => 'External ID'],
                    ['key' => 'id', 'label' => 'ID'],
                ])
                ->where('import.mapping', ['article', 'name', 'price', 'external_id', 'section_id', 'photos'])
                ->where('import.options', ['mode' => 'both', 'separator' => '/', 'create_sections' => false])
                ->where('targets', static fn ($targets): bool => in_array(['key' => 'section_id', 'label' => 'Section'], collect($targets)->all(), true)
                    && in_array(['key' => 'external_id', 'label' => 'External ID'], collect($targets)->all(), true)));
    }

    public function test_the_option_of_the_catalog_is_on_no_other_modules_screen(): void
    {
        $this->assertSame([], Settings::options(Brand::class));

        $import = $this->upload('brands', "Name\nAcme\n");

        $this->get("/admin/brands/import/{$import->id}")
            ->assertOk()
            ->assertInertia(static fn (AssertableInertia $page) => $page
                ->where('moduleOptions', [])
                ->where('import.options', ['mode' => 'both']));
    }

    public function test_the_hook_adds_the_option_for_the_products_only(): void
    {
        $this->assertSame([], Hook::apply('atrium.import.options', [], Brand::class));
        $this->assertSame(['create_sections'], array_map(static fn ($option): string => $option->name, Hook::apply('atrium.import.options', [], Product::class)));
        $this->assertCount(2, Settings::options(Product::class));
        $this->assertSame(['mode' => 'both', 'separator' => '/', 'create_sections' => false], Settings::defaults(Settings::options(Product::class)));
    }

    public function test_a_file_is_checked_and_imported_from_the_screens_and_makes_the_sections_it_is_told_to(): void
    {
        CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $import = $this->upload('catalog_products', "SKU;Product;Cost;Category\nA-1;Parka;10;Одежда / Куртки\nA-2;Boots;20;Одежда\n");
        $settings = ['mapping' => $import->mapping, 'key' => 'article', 'mode' => 'both', 'separator' => '/', 'create_sections' => true];

        $this->assertSame(['article', 'name', 'price', 'section_id'], $import->mapping);

        $this->put("/admin/catalog_products/import/{$import->id}", $settings)->assertRedirect("/admin/catalog_products/import/{$import->id}");
        $checked = $this->advance($import);

        $this->assertSame([DataImport::CHECKED, 2, 0, 1, true], [$checked['status'], $checked['created'], $checked['failed'], $checked['nodes'], $checked['options']['create_sections']]);
        $this->assertSame([1, 0], [CatalogSection::query()->count(), Product::query()->count()]);

        $this->post("/admin/catalog_products/import/{$import->id}/run")->assertRedirect();
        $done = $this->advance($import);

        $this->assertSame([DataImport::DONE, 2, 1], [$done['status'], $done['created'], $done['nodes']]);
        $jackets = CatalogSection::query()->where('name', 'Куртки')->sole();
        $this->assertSame(['clothes', true], [CatalogSection::query()->find($jackets->parent_id)?->code, (bool) $jackets->active]);
        $this->assertSame([$jackets->id, $jackets->parent_id], Product::query()->orderBy('article')->pluck('section_id')->all());
    }

    public function test_without_the_option_checked_on_the_screen_a_missing_section_is_an_error(): void
    {
        $import = $this->upload('catalog_products', "SKU;Product;Category\nA-1;Parka;Куртки\n");
        $settings = ['mapping' => $import->mapping, 'key' => 'article', 'mode' => 'both', 'separator' => '/'];

        $this->put("/admin/catalog_products/import/{$import->id}", $settings)->assertRedirect();
        $checked = $this->advance($import);

        $this->assertSame([DataImport::CHECKED, 0, 1, 0, false], [$checked['status'], $checked['created'], $checked['failed'], $checked['nodes'], $checked['options']['create_sections']]);
        $this->assertSame([['row' => 2, 'skipped' => false, 'messages' => ['"Section": there is no "Куртки".']]], $checked['errors']);
        $this->assertSame(0, CatalogSection::query()->count());
    }

    public function test_the_screen_takes_the_option_as_yes_or_no_only(): void
    {
        $import = $this->upload('catalog_products', "SKU;Product\nA-1;Parka\n");
        $settings = ['mapping' => $import->mapping, 'key' => 'article', 'mode' => 'both', 'separator' => '/'];

        $this->put("/admin/catalog_products/import/{$import->id}", [...$settings, 'create_sections' => 'maybe'])
            ->assertSessionHasErrors(['create_sections' => 'The Create the sections that are not there field must be true or false.']);
        $this->assertSame(DataImport::NEW, $import->refresh()->status);
    }
}
