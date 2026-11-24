<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Mahsulotlar',
        'sections' => 'Katalog boʻlimlari',
    ],
    'create' => [
        'product' => 'Mahsulot qoʻshish',
    ],
    'fields' => [
        'article' => 'Artikul',
        'external_id' => 'Tashqi kod',
        'price' => 'Narx',
        'brand' => 'Brend',
        'stores' => 'Qayerdan sotib olish mumkin',
        'label' => 'Belgi',
        'colors' => 'Ranglar',
        'released_on' => 'Chiqarilgan sana',
        'in_stock' => 'Mavjud',
        'features' => 'Xususiyatlar',
        'related' => 'Bunga mos',
        'specs' => 'Texnik tavsif',
        'package' => 'Toʻplam',
        'photos' => 'Fotosuratlar',
        'manual' => 'Qoʻllanma',
    ],
    'options' => [
        'label' => [
            'new' => 'Yangi',
            'hit' => 'Xit',
            'sale' => 'Chegirma',
        ],
        'colors' => [
            'black' => 'Qora',
            'silver' => 'Kumush',
            'blue' => 'Koʻk',
            'pink' => 'Pushti',
        ],
    ],
    'settings' => [
        'per_page' => 'Sahifadagi mahsulotlar',
        'per_page_hint' => 'Ushbu saytda katalog bo‘limining sahifasi nechta mahsulot ko‘rsatadi. Bo‘sh: mavzuning standart qiymati.',
        'market_name' => 'Yandex Market uchun do‘kon nomi',
        'market_name_hint' => 'Yandex Market ko‘rsatadigan qisqa nom, 20 belgigacha. Bo‘sh bo‘lsa — saytning nomi.',
        'market_company' => 'Kompaniya',
        'market_company_hint' => 'Sotuvchi kompaniyaning to‘liq nomi. Bo‘sh bo‘lsa — saytning nomi.',
        'market_currency' => 'Narxlar valyutasi',
        'market_currency_hint' => 'Mahsulot narxlari qaysi valyutada ko‘rsatilgan. Yandex Market RUB, BYN, KZT, UAH, USD va EUR ni qabul qiladi.',
    ],
];
