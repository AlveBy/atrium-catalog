<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Produkty',
        'sections' => 'Działy katalogu',
    ],
    'create' => [
        'product' => 'Dodaj produkt',
    ],
    'fields' => [
        'article' => 'Numer katalogowy',
        'external_id' => 'Zewnętrzny ID',
        'price' => 'Cena',
        'brand' => 'Marka',
        'stores' => 'Gdzie kupić',
        'label' => 'Etykieta',
        'colors' => 'Kolory',
        'released_on' => 'Data premiery',
        'in_stock' => 'Dostępny',
        'features' => 'Cechy',
        'related' => 'Pasuje do',
        'specs' => 'Specyfikacja',
        'package' => 'Zawartość zestawu',
        'photos' => 'Zdjęcia',
        'manual' => 'Instrukcja',
    ],
    'options' => [
        'label' => [
            'new' => 'Nowość',
            'hit' => 'Bestseller',
            'sale' => 'Wyprzedaż',
        ],
        'colors' => [
            'black' => 'Czarny',
            'silver' => 'Srebrny',
            'blue' => 'Niebieski',
            'pink' => 'Różowy',
        ],
    ],
    'settings' => [
        'per_page' => 'Produktów na stronę',
        'per_page_hint' => 'Ile produktów pokazuje strona działu katalogu w tej witrynie. Puste: domyślna wartość motywu.',
        'market_name' => 'Nazwa sklepu dla Yandex Market',
        'market_name_hint' => 'Krótka nazwa wyświetlana przez Yandex Market, do 20 znaków. Puste: nazwa witryny.',
        'market_company' => 'Firma',
        'market_company_hint' => 'Pełna nazwa firmy sprzedającej. Puste: nazwa witryny.',
        'market_currency' => 'Waluta cen',
        'market_currency_hint' => 'Waluta, w której podano ceny produktów. Yandex Market przyjmuje RUB, BYN, KZT, UAH, USD i EUR.',
    ],
];
