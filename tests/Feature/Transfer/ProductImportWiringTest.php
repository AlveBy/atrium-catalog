<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Transfer\ImportOption;
use Atrium\Admin\Transfer\Settings;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Once the catalog is installed its products take part in the import and
 * the export every module has.
 */
final class ProductImportWiringTest extends TestCase
{
    public function test_the_catalog_hooks_into_the_import(): void
    {
        $this->assertTrue(Hook::has('atrium.import.options'));
        $this->assertTrue(Hook::has('atrium.import.targets'));
    }

    public function test_the_products_have_the_import_screens_and_the_export(): void
    {
        foreach (['import', 'import.store', 'import.show', 'import.check', 'import.run', 'import.step', 'import.cancel', 'export'] as $name) {
            $this->assertTrue(Route::has('admin.catalog_products.'.$name), $name);
        }

        $this->assertSame('admin/catalog_products/import', Route::getRoutes()->getByName('admin.catalog_products.import')?->uri());
        $this->assertSame('admin/catalog_products/export', Route::getRoutes()->getByName('admin.catalog_products.export')?->uri());
    }

    public function test_the_option_of_the_catalog_comes_after_the_options_others_add_for_the_products_and_for_them_only(): void
    {
        Hook::filter('atrium.import.options', static fn (array $options, string $model): array => [...$options, new ImportOption('before', 'Before')], 5);

        $this->assertSame(['separator', 'before', 'create_sections'], array_column(Settings::options(Product::class), 'name'));
        $this->assertSame(['separator', 'before'], array_column(Settings::options(CatalogSection::class), 'name'));
    }
}
