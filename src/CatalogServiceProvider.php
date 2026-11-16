<?php

declare(strict_types=1);

namespace Atrium\Catalog;

use Illuminate\Support\ServiceProvider;

/**
 * The models, their migrations and its demo are in the manifest (composer.json,
 * extra.atrium): they are on once `atrium:module:install` has run.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-catalog');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-catalog'),
            ], 'atrium-catalog-lang');
        }
    }
}
