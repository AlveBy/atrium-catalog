<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog;

use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Modules\ModuleSettings;
use Atrium\Catalog\Feed\MarketFeed;

/**
 * What can be set for the catalog, per site: how many products a page of a
 * catalog section shows, and what the Yandex Market feed says about the shop.
 * Read with `Setting::get('catalog.per_page')`, `catalog.market_name`,
 * `catalog.market_company` and `catalog.market_currency`.
 */
final class CatalogSettings extends ModuleSettings
{
    public bool $perSite = true;

    /** What Yandex Market takes as the currency of the prices. */
    public const CURRENCIES = ['RUB', 'BYN', 'KZT', 'UAH', 'USD', 'EUR'];

    public function fields(): array
    {
        return [
            NumberField::make('per_page')
                ->label(__('atrium-catalog::catalog.settings.per_page'))
                ->hint(__('atrium-catalog::catalog.settings.per_page_hint'))
                ->rules(['integer', 'min:1', 'max:100']),
            TextField::make('market_name')
                ->label(__('atrium-catalog::catalog.settings.market_name'))
                ->hint(__('atrium-catalog::catalog.settings.market_name_hint'))
                ->rules(['max:'.MarketFeed::NAME_LENGTH]),
            TextField::make('market_company')
                ->label(__('atrium-catalog::catalog.settings.market_company'))
                ->hint(__('atrium-catalog::catalog.settings.market_company_hint'))
                ->rules(['max:255']),
            SelectField::make('market_currency')
                ->label(__('atrium-catalog::catalog.settings.market_currency'))
                ->hint(__('atrium-catalog::catalog.settings.market_currency_hint'))
                ->options(array_combine(self::CURRENCIES, self::CURRENCIES))
                ->default('RUB'),
        ];
    }
}
