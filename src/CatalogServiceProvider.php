<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog;

use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Catalog\Http\MarketFeedController;
use Atrium\Catalog\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The models, their migrations and its demo are in the manifest (composer.json,
 * extra.atrium): they are on once `atrium:module:install` has run. So is what
 * the catalog adds to the import every module has (ProductImport). The
 * Yandex Market feed is the module's own route.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-catalog';

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-catalog');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-catalog'),
            ], 'atrium-catalog-lang');
        }

        if (! $this->app->make(Modules::class)->isInstalled(self::MODULE)) {
            return;
        }

        ProductImport::register();

        // Once every module has told where its pages are, and the panel has
        // booted: Route::localized() is its macro, the prefix is in its map.
        $this->app->booted(function (): void {
            $this->registerMarketFeed();
        });
    }

    /**
     * /catalog.xml next to the catalog at /catalog (the prefix is the
     * module's address): a route of its own, so it never meets the theme's
     * /catalog/{path}.
     */
    private function registerMarketFeed(): void
    {
        $prefix = PublicUrl::ruleFor(Product::class)['prefix'] ?? '';

        if ($prefix === '' || $this->app->routesAreCached()) {
            return;
        }

        Route::middleware('web')->group(function () use ($prefix): void {
            Route::localized(function () use ($prefix): void {
                Route::get($prefix.'.xml', MarketFeedController::class)->name('atrium.catalog.market');
            });
        });
    }
}
