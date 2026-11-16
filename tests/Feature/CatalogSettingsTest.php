<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\Catalog\Tests\TestCase;

/**
 * The catalog's settings: how many products a page shows, per site.
 */
final class CatalogSettingsTest extends TestCase
{
    public function test_products_per_page_is_set_for_each_site(): void
    {
        $this->seedDemo();

        $this->get('/catalog')->assertOk()->assertDontSee('page=2');

        $default = app(Sites::class)->default();
        $outlet = Site::query()->where('code', '!=', $default->code)->firstOrFail();
        Setting::save('catalog', ['per_page' => 2], $default);

        $this->assertSame(2, Setting::get('catalog.per_page', null, $default));
        $this->assertNull(Setting::get('catalog.per_page', null, $outlet));

        $this->get('/catalog')->assertOk()->assertSee('page=2');
    }

    public function test_a_superadmin_sets_it_in_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/modules/catalog/settings')
            ->assertOk()
            ->assertSee('Products per page');

        $this->actingAs($admin, 'admin')
            ->put('/admin/modules/catalog/settings', ['per_page' => 500])
            ->assertSessionHasErrors('per_page');
    }
}
