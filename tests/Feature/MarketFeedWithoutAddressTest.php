<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * A site that gave the catalog no address has no feed either.
 */
final class MarketFeedWithoutAddressTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.public_urls', [Product::class => ['prefix' => '']]);
    }

    public function test_there_is_no_feed(): void
    {
        $this->assertFalse(Route::has('atrium.catalog.market'));
        $this->get('/.xml')->assertNotFound();
        $this->get('/catalog.xml')->assertNotFound();
    }
}
