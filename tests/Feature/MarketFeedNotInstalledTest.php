<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Resources\ResourceRegistry;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Required with Composer is not installed: until `atrium:module:install`
 * the catalog is not in the panel, the site or the database, and has no feed.
 */
final class MarketFeedNotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
        // An address rule is there (the app's own), the module is not.
        $app['config']->set('atrium.public_urls', [Product::class => ['prefix' => 'catalog']]);
    }

    public function test_a_catalog_required_but_not_installed_has_no_feed(): void
    {
        $this->assertArrayNotHasKey('catalog_products', $this->app->make(ResourceRegistry::class)->all());
        $this->assertFalse(Schema::hasTable('catalog_products'));
        $this->assertFalse(Route::has('atrium.catalog.market'));
        $this->get('/catalog.xml')->assertNotFound();
    }
}
