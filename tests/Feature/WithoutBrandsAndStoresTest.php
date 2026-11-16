<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Catalog\Database\CatalogDemoSeeder;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

/**
 * The catalog alone: the Brands and Stores modules are required with
 * Composer but not installed, so their tables are not there.
 */
final class WithoutBrandsAndStoresTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', ['alveby/atrium-catalog']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue((new CatalogDemoSeeder)->run());
    }

    public function test_the_form_and_the_list_leave_the_brand_and_the_stores_out(): void
    {
        $this->assertFalse(Schema::hasTable('brands'));
        $this->assertFalse(Schema::hasTable('stores'));

        $fields = array_map(static fn (Field $field): string => $field->getName(), Product::getAdministrationFields());
        $this->assertNotContains('brand_id', $fields);
        $this->assertNotContains('stores', $fields);
        $this->assertNotContains('brand.name', array_map(static fn ($column): string => $column->getName(), Product::getAdministrationListColumns()));

        $product = Product::query()->where('code', 'aero-14')->sole();

        $this->actingAs($this->admin(), 'admin');
        $this->get('/admin/catalog_products')->assertOk();
        $this->get("/admin/catalog_products/{$product->id}/edit")->assertOk();
        $this->put("/admin/catalog_products/{$product->id}", ['name' => 'Aero 14', 'price' => 1099])->assertRedirect('/admin/catalog_products');
        $this->assertSame('1099.00', $product->fresh()?->price);
    }

    public function test_the_relations_find_nothing_and_query_no_missing_table(): void
    {
        $product = Product::query()->with(['brand', 'stores'])->where('code', 'aero-14')->sole();

        $this->assertNull($product->brand_id);
        $this->assertNull($product->brand);
        $this->assertCount(0, $product->stores);
        $this->assertSame(0, Product::query()->whereHas('stores')->count());
    }

    public function test_the_product_page_works_without_them(): void
    {
        $this->get('/catalog/computers/laptops/aero-14')
            ->assertOk()
            ->assertSeeInOrder(['LM-A14', '1199.00', 'Bestseller'])
            // The SEO description names the brand; the properties do not.
            ->assertDontSee('>Lumen<', false)
            ->assertDontSee('/stores/');
    }
}
