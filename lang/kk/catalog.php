<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Тауарлар',
        'sections' => 'Каталог бөлімдері',
    ],
    'create' => [
        'product' => 'Тауар қосу',
    ],
    'fields' => [
        'article' => 'Артикул',
        'external_id' => 'Сыртқы код',
        'price' => 'Бағасы',
        'brand' => 'Бренд',
        'stores' => 'Қайдан сатып алуға болады',
        'label' => 'Белгі',
        'colors' => 'Түстер',
        'released_on' => 'Шыққан күні',
        'in_stock' => 'Қоймада бар',
        'features' => 'Ерекшеліктері',
        'related' => 'Бұнымен бірге алады',
        'specs' => 'Сипаттамалары',
        'package' => 'Жиынтығы',
        'photos' => 'Фотосуреттер',
        'manual' => 'Нұсқаулық',
    ],
    'options' => [
        'label' => [
            'new' => 'Жаңа',
            'hit' => 'Хит',
            'sale' => 'Жеңілдік',
        ],
        'colors' => [
            'black' => 'Қара',
            'silver' => 'Күміс',
            'blue' => 'Көк',
            'pink' => 'Қызғылт',
        ],
    ],
    'settings' => [
        'per_page' => 'Беттегі тауарлар',
        'per_page_hint' => 'Осы сайтта каталог бөлімінің беті қанша тауар көрсетеді. Бос болса — тақырыптың әдепкі мәні.',
        'market_name' => 'Яндекс Маркет үшін дүкен атауы',
        'market_name_hint' => 'Яндекс Маркет көрсететін қысқа атау, 20 таңбаға дейін. Бос болса — сайттың атауы.',
        'market_company' => 'Компания',
        'market_company_hint' => 'Сатушы компанияның толық атауы. Бос болса — сайттың атауы.',
        'market_currency' => 'Бағалардың валютасы',
        'market_currency_hint' => 'Тауар бағалары қай валютада көрсетілген. Яндекс Маркет RUB, BYN, KZT, UAH, USD және EUR қабылдайды.',
    ],
];
