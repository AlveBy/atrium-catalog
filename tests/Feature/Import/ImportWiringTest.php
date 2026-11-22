<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\Http\Middleware\AuthorizeImport;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Attributes\DefineEnvironment;

/**
 * The import's screens follow the host's middleware, in order.
 */
final class ImportWiringTest extends TestCase
{
    #[DefineEnvironment('twoMiddleware')]
    public function test_the_import_routes_take_the_middleware_of_the_host_in_order(): void
    {
        $this->assertSame(['web', 'throttle:90,1', 'atrium.inertia', 'admin.auth', 'atrium.site', AuthorizeImport::class], $this->route()->middleware());
    }

    #[DefineEnvironment('noMiddleware')]
    public function test_a_host_that_names_no_middleware_gets_the_web_group(): void
    {
        $this->assertSame(['web', 'atrium.inertia', 'admin.auth', 'atrium.site', AuthorizeImport::class], $this->route()->middleware());
    }

    public function twoMiddleware(Application $app): void
    {
        $app['config']->set('atrium.middleware', ['web', 'throttle:90,1']);
    }

    public function noMiddleware(Application $app): void
    {
        $atrium = $app['config']->get('atrium');
        unset($atrium['middleware']);
        $app['config']->set('atrium', $atrium);
    }

    private function route(): RoutingRoute
    {
        $route = Route::getRoutes()->getByName('admin.catalog-import.index');
        $this->assertNotNull($route);

        return $route;
    }
}
