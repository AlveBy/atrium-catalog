<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Produkte',
        'sections' => 'Katalogbereiche',
    ],
    'create' => [
        'product' => 'Produkt hinzufügen',
    ],
    'fields' => [
        'article' => 'Artikelnummer',
        'external_id' => 'Externe ID',
        'price' => 'Preis',
        'brand' => 'Marke',
        'stores' => 'Erhältlich in',
        'label' => 'Kennzeichnung',
        'colors' => 'Farben',
        'released_on' => 'Erscheinungsdatum',
        'in_stock' => 'Auf Lager',
        'features' => 'Merkmale',
        'related' => 'Passt gut dazu',
        'specs' => 'Technische Daten',
        'package' => 'Lieferumfang',
        'photos' => 'Fotos',
        'manual' => 'Anleitung',
    ],
    'options' => [
        'label' => [
            'new' => 'Neu',
            'hit' => 'Bestseller',
            'sale' => 'Angebot',
        ],
        'colors' => [
            'black' => 'Schwarz',
            'silver' => 'Silber',
            'blue' => 'Blau',
            'pink' => 'Rosa',
        ],
    ],
    'settings' => [
        'per_page' => 'Produkte pro Seite',
        'per_page_hint' => 'Wie viele Produkte eine Seite eines Katalogbereichs auf dieser Website zeigt. Leer: die Voreinstellung des Themes.',
        'market_name' => 'Shopname für Yandex Market',
        'market_name_hint' => 'Der Kurzname, den Yandex Market zeigt, bis zu 20 Zeichen. Leer: der Name der Website.',
        'market_company' => 'Unternehmen',
        'market_company_hint' => 'Der vollständige Name des verkaufenden Unternehmens. Leer: der Name der Website.',
        'market_currency' => 'Währung der Preise',
        'market_currency_hint' => 'Die Währung, in der die Produktpreise angegeben sind. Yandex Market akzeptiert RUB, BYN, KZT, UAH, USD und EUR.',
    ],
];
