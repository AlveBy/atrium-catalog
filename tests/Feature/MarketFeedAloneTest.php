<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Support\RecordUrls;
use Atrium\Catalog\CatalogServiceProvider;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Tighten\Ziggy\ZiggyServiceProvider;

/**
 * The feed of the catalog alone: no public theme, and the Brands module is
 * required with Composer but not installed.
 */
final class MarketFeedAloneTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', ['alveby/atrium-catalog']);
    }

    protected function getPackageProviders($app): array
    {
        return [InertiaServiceProvider::class, ZiggyServiceProvider::class, AtriumServiceProvider::class, CatalogServiceProvider::class];
    }

    private function product(string $name, array $attributes = []): Product
    {
        $section = CatalogSection::query()->firstOrCreate(['code' => 'laptops'], ['name' => 'Laptops', 'active' => true, 'sort' => 500]);

        return Product::query()->create($attributes + ['name' => $name, 'code' => str($name)->slug()->toString(), 'section_id' => $section->id, 'active' => true, 'price' => 100, 'in_stock' => 'Y', 'sort' => 500]);
    }

    public function test_the_addresses_come_from_the_catalog_rule(): void
    {
        $this->product('Aero 14');

        $content = $this->get('/catalog.xml')->assertOk()->streamedContent();

        $this->assertStringContainsString('<url>'.$this->root().'/catalog/laptops/aero-14</url>', $content);
    }

    public function test_a_product_that_has_no_page_is_left_out(): void
    {
        $this->product('Aero 14');
        $this->product('Hidden');
        $this->app->make(RecordUrls::class)->extend(static fn (Model $record): string|false|null => $record instanceof Product && $record->name === 'Hidden' ? false : null);

        $content = $this->get('/catalog.xml')->assertOk()->streamedContent();

        $this->assertStringContainsString('Aero 14', $content);
        $this->assertStringNotContainsString('Hidden', $content);
    }

    public function test_there_is_no_vendor_and_no_brands_table_is_read(): void
    {
        $this->product('Aero 14', ['brand_id' => 1, 'article' => 'AE14']);

        DB::enableQueryLog();
        $content = $this->get('/catalog.xml')->assertOk()->streamedContent();

        $this->assertStringNotContainsString('<vendor>', $content);
        $this->assertStringContainsString('<vendorCode>AE14</vendorCode>', $content);
        $this->assertSame([], array_filter(DB::getQueryLog(), static fn (array $query): bool => str_contains($query['query'], 'brands')));
    }
}
