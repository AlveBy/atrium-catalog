<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Modules\Modules;
use Atrium\Catalog\Console\ImportCommand;
use Atrium\Catalog\Http\Controllers\ImportController;
use Atrium\Catalog\Http\Middleware\AuthorizeImport;
use Atrium\Catalog\Models\Product;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The models, their migrations and its demo are in the manifest (composer.json,
 * extra.atrium): they are on once `atrium:module:install` has run. So is the
 * import of products: its screens, the button above the products and the
 * scheduled step for sites without a queue worker.
 */
final class CatalogServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-catalog';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-catalog.php', 'atrium-catalog');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-catalog');

        if ($this->app->runningInConsole()) {
            $this->commands([ImportCommand::class]);

            $this->publishes([
                __DIR__.'/../config/atrium-catalog.php' => config_path('atrium-catalog.php'),
            ], 'atrium-catalog-config');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-catalog'),
            ], 'atrium-catalog-lang');
        }

        if (! $this->app->make(Modules::class)->isInstalled(self::MODULE)) {
            return;
        }

        Hook::filter('atrium.module.links', static fn (array $links, string $model): array => $model === Product::class
            && AuthorizeImport::allows(auth()->guard(config('atrium.auth.guard', 'admin'))->user())
                ? [...$links, ['name' => 'import', 'label' => __('atrium-catalog::import.title'), 'href' => route('admin.catalog-import.index')]]
                : $links, 20);

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command('atrium:catalog:import --pending')->everyMinute()->withoutOverlapping();
        });

        // Once the panel has booted: its route macros and groups are there.
        $this->app->booted(function (): void {
            if (! $this->app->routesAreCached()) {
                $this->registerRoutes();
            }
        });
    }

    private function registerRoutes(): void
    {
        Route::prefix(config('atrium.prefix', 'admin'))
            ->middleware([...config('atrium.middleware', ['web']), 'atrium.inertia', 'admin.auth', 'atrium.site', AuthorizeImport::class])
            ->name('admin.catalog-import.')
            ->group(static function (): void {
                Route::get('catalog-import', [ImportController::class, 'index'])->name('index');
                Route::post('catalog-import', [ImportController::class, 'store'])->name('store');
                Route::get('catalog-import/{import}', [ImportController::class, 'show'])->whereNumber('import')->name('show');
                Route::put('catalog-import/{import}', [ImportController::class, 'check'])->whereNumber('import')->name('check');
                Route::post('catalog-import/{import}/run', [ImportController::class, 'run'])->whereNumber('import')->name('run');
                Route::post('catalog-import/{import}/step', [ImportController::class, 'step'])->whereNumber('import')->name('step');
                Route::post('catalog-import/{import}/cancel', [ImportController::class, 'cancel'])->whereNumber('import')->name('cancel');
            });
    }
}
