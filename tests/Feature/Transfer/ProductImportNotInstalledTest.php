<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Transfer\Target;
use Atrium\Catalog\CatalogServiceProvider;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Until the catalog is installed the import of the products is not there:
 * nothing is hooked into the import of the other modules, and nothing breaks.
 */
final class ProductImportNotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_the_catalog_hooks_nothing_into_the_import(): void
    {
        $this->assertFalse($this->app->make(Modules::class)->isInstalled(CatalogServiceProvider::MODULE));
        $this->assertFalse(Hook::has('atrium.import.options'));
        $this->assertFalse(Hook::has('atrium.import.targets'));
    }

    public function test_the_filters_leave_what_they_get_as_it_is(): void
    {
        $targets = ['name' => new Target('name', 'Name'), 'section_id' => new Target('section_id', 'Section')];

        $this->assertSame([], Hook::apply('atrium.import.options', [], Product::class));
        $this->assertSame($targets, Hook::apply('atrium.import.targets', $targets, Product::class));
        $this->assertSame([], $targets['name']->synonyms);
    }

    public function test_the_products_have_no_import_screens_and_no_export(): void
    {
        $this->assertFalse(Route::has('admin.catalog_products.import'));
        $this->assertFalse(Route::has('admin.catalog_products.import.store'));
        $this->assertFalse(Route::has('admin.catalog_products.export'));
    }

    public function test_the_console_says_there_is_no_such_module(): void
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => __FILE__])
            ->expectsOutput('No module "catalog_products" takes imports. The ones that do: .')
            ->assertExitCode(1);

        $this->artisan('atrium:export', ['module' => 'catalog_products'])
            ->expectsOutput('No module "catalog_products" can be exported. The ones that can: sites, languages.')
            ->assertExitCode(1);
    }

    public function test_the_words_of_the_option_are_there_all_the_same(): void
    {
        $this->assertSame('Create the sections that are not there', __('atrium-catalog::import.create_sections'));
    }
}
