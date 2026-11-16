<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog;

use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * What can be set for the catalog, per site: how many products a page of a
 * catalog section shows. Read with `Setting::get('catalog.per_page')`.
 */
final class CatalogSettings extends ModuleSettings
{
    public bool $perSite = true;

    public function fields(): array
    {
        return [
            NumberField::make('per_page')
                ->label(__('atrium-catalog::catalog.settings.per_page'))
                ->hint(__('atrium-catalog::catalog.settings.per_page_hint'))
                ->rules(['nullable', 'integer', 'min:1', 'max:100']),
        ];
    }
}
