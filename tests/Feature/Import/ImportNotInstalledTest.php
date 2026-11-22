<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Facades\Hook;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;

/**
 * Until the catalog is installed its import is not there either: no screens,
 * no button, nothing scheduled. The console command is, and says so itself.
 */
final class ImportNotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_nothing_of_the_import_is_on(): void
    {
        $this->assertFalse(Route::has('admin.catalog-import.index'));
        $this->assertSame([], Hook::apply('atrium.module.links', [], Product::class));
        $this->assertSame([], array_values(array_filter(
            app(Schedule::class)->events(),
            static fn ($event): bool => str_contains((string) $event->command, 'atrium:catalog:import'),
        )));
    }
}
