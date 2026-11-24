<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The catalog module: products and their sections.
 */
return [
    'modules' => [
        'products' => 'Products',
        'sections' => 'Catalog sections',
    ],
    'create' => [
        'product' => 'Add product',
    ],
    'fields' => [
        'article' => 'Article number',
        'external_id' => 'External ID',
        'price' => 'Price',
        'brand' => 'Brand',
        'stores' => 'Where to buy',
        'label' => 'Label',
        'colors' => 'Colours',
        'released_on' => 'Released',
        'in_stock' => 'In stock',
        'features' => 'Features',
        'related' => 'Goes well with',
        'specs' => 'Specifications',
        'package' => 'In the box',
        'photos' => 'Photos',
        'manual' => 'Manual',
    ],
    'options' => [
        'label' => [
            'new' => 'New',
            'hit' => 'Bestseller',
            'sale' => 'Sale',
        ],
        'colors' => [
            'black' => 'Black',
            'silver' => 'Silver',
            'blue' => 'Blue',
            'pink' => 'Pink',
        ],
    ],
    'settings' => [
        'per_page' => 'Products per page',
        'per_page_hint' => 'How many products a page of a catalog section shows on this site. Empty: the theme\'s default.',
        'market_name' => 'Shop name for Yandex Market',
        'market_name_hint' => 'The short name Yandex Market shows, up to 20 characters. Empty: the site name.',
        'market_company' => 'Company',
        'market_company_hint' => 'The full name of the company that sells. Empty: the site name.',
        'market_currency' => 'Currency of the prices',
        'market_currency_hint' => 'The currency the product prices are in. Yandex Market takes RUB, BYN, KZT, UAH, USD and EUR.',
    ],
];
