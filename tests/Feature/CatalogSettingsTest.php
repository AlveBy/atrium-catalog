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

    public function test_the_shop_for_yandex_market_is_set_in_the_panel(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/admin/modules/catalog/settings')
            ->assertOk()
            ->assertSee('Shop name for Yandex Market')
            ->assertSee('Currency of the prices');

        foreach ([
            'market_name' => str_repeat('a', 21),
            'market_company' => str_repeat('a', 256),
            'market_currency' => 'GBP',
        ] as $field => $value) {
            $this->actingAs($admin, 'admin')
                ->put('/admin/modules/catalog/settings', [$field => $value, 'market_currency' => $field === 'market_currency' ? $value : 'RUB'])
                ->assertSessionHasErrors($field);
        }

        $this->actingAs($admin, 'admin')
            ->put('/admin/modules/catalog/settings', ['market_currency' => ''])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin, 'admin')
            ->put('/admin/modules/catalog/settings', ['market_name' => str_repeat('a', 20), 'market_company' => 'Aero Trading LLC', 'market_currency' => 'KZT'])
            ->assertSessionHasNoErrors();

        $this->assertSame('KZT', Setting::get('catalog.market_currency', null, app(Sites::class)->default()));
        $this->assertSame(str_repeat('a', 20), Setting::get('catalog.market_name', null, app(Sites::class)->default()));
    }
}
