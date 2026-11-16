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
    ],
];
