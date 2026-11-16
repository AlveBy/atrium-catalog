<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\Field;
use Atrium\Admin\Models\SeoMeta;
use Atrium\Admin\Models\Slug;
use Atrium\Admin\Models\SlugHistory;
use Atrium\Admin\Modules\Modules;
use Atrium\Catalog\Database\CatalogDemoSeeder;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Inertia\Testing\AssertableInertia;
use ReflectionClass;

/**
 * The demo shop of `atrium:install --demo` as a user of the admin sees it,
 * and the model traits the catalog is the only user of in the repository.
 */
final class DemoShopTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDemo();
    }

    public function test_every_field_type_of_the_panel_is_in_the_demo(): void
    {
        $all = [];

        foreach (glob(dirname((string) (new ReflectionClass(Field::class))->getFileName()).'/*Field.php') ?: [] as $file) {
            $class = 'Atrium\\Admin\\Fields\\'.basename($file, '.php');

            if ($class !== Field::class) {
                $all[] = (new $class('x'))->getType();
            }
        }

        $used = [];

        foreach ($this->models() as $model) {
            foreach ($model::getAdministrationFields() as $field) {
                $used[] = $field->getType();
            }
        }

        $this->assertCount(21, $all);
        $this->assertSame([], array_values(array_diff($all, $used)));
    }

    public function test_every_demo_module_opens_in_the_admin(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach ($this->models() as $model) {
            $key = $model::getAdminRoutePrefix();
            $record = $model::query()->firstOrFail();

            $this->get("/admin/{$key}")->assertOk();
            $this->get("/admin/{$key}/create")->assertOk();
            $this->get("/admin/{$key}/{$record->getKey()}/edit")->assertOk();
        }

        $this->get('/admin/catalog_products')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', 'Products')
                ->where('items.total', 8)
                ->where('items.data.0.name', 'Aero 14'));

        // The brand's name, from the Brands module, in a column of the list.
        $this->assertSame('Lumen', $this->get('/admin/catalog_products')->viewData('page')['props']['items']['data'][0]['brand.name']);
    }

    public function test_a_link_to_a_directory_is_a_list_of_its_records(): void
    {
        $brand = collect(Product::getAdministrationFields())->first(fn (Field $field): bool => $field->getName() === 'brand_id');

        $this->assertSame(['Lumen', 'Vantor', 'Orbita'], array_column($brand?->toArray()['options'] ?? [], 'label'));
    }

    public function test_the_product_form_has_the_bitrix_tabs_and_the_seo_tab(): void
    {
        $grouped = Product::getFieldsGroupedByTabs('edit');

        $this->assertSame(['params', 'preview', 'detail', 'seo', 'sections'], array_column($grouped['tabs'], 'name'));
        $this->assertContains('seo_title', array_map(static fn (Field $field): string => $field->getName(), $grouped['fields']['seo']));
    }

    public function test_a_product_keeps_its_old_code_and_its_seo(): void
    {
        $product = Product::query()->where('code', 'aero-14')->sole();

        $this->assertSame('aero-14', Slug::query()->where('sluggable_type', Product::class)->where('sluggable_id', $product->id)->value('slug'));
        $this->assertSame('Aero 14 laptop: 1.2 kg, 18 hours of battery', $product->getSeoTitle());
        $this->assertSame(1, SeoMeta::query()->count());

        $product->update(['code' => 'aero-14-2026']);

        $this->assertSame('aero-14', SlugHistory::query()->where('sluggable_id', $product->id)->value('slug'));
        $this->assertSame('aero-14-2026', Slug::query()->where('sluggable_id', $product->id)->where('sluggable_type', Product::class)->value('slug'));

        // A new product without a code gets one from its name.
        $this->assertSame('pulse-max', Product::query()->create(['name' => 'Pulse Max', 'price' => 129])->code);
    }

    public function test_files_attached_in_code_have_an_address(): void
    {
        $product = Product::query()->where('code', 'aero-14')->sole();

        $this->assertTrue($product->hasFile('manual'));
        $this->assertStringEndsWith('/demo/quick-start.pdf', (string) $product->getFileUrl('manual'));
        $this->assertSame('Aero 14 quick start.pdf', $product->getFileByField('manual')?->filename);
    }

    public function test_a_product_links_to_its_brand_and_its_stores(): void
    {
        $product = Product::query()->where('code', 'aero-14')->sole();

        $this->assertSame('Lumen', $product->brand?->getAttribute('name'));
        $this->assertSame(['City centre', 'Riverside outlet'], $product->stores()->orderBy('name')->pluck('name')->all());
        $this->assertSame(['City centre'], Product::query()->where('code', 'stride')->sole()->stores->pluck('name')->all());
    }

    public function test_seeding_twice_adds_nothing(): void
    {
        $this->assertFalse((new CatalogDemoSeeder)->run());
        $this->assertSame(8, Product::query()->count());
    }

    /**
     * @return array<int,class-string<Model&Administrable>>
     */
    private function models(): array
    {
        $modules = $this->app->make(Modules::class);

        return array_merge(...array_values(array_map(static fn ($module): array => $module->resources, $modules->installed())));
    }
}
