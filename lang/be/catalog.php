<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Тавары',
        'sections' => 'Раздзелы каталога',
    ],
    'create' => [
        'product' => 'Дадаць тавар',
    ],
    'fields' => [
        'article' => 'Артыкул',
        'external_id' => 'Знешні код',
        'price' => 'Цана',
        'brand' => 'Брэнд',
        'stores' => 'Дзе купіць',
        'label' => 'Метка',
        'colors' => 'Колеры',
        'released_on' => 'Дата выхаду',
        'in_stock' => 'У наяўнасці',
        'features' => 'Асаблівасці',
        'related' => 'З гэтым бяруць',
        'specs' => 'Характарыстыкі',
        'package' => 'Камплектацыя',
        'photos' => 'Фатаграфіі',
        'manual' => 'Інструкцыя',
    ],
    'options' => [
        'label' => [
            'new' => 'Навінка',
            'hit' => 'Хіт продажаў',
            'sale' => 'Распродаж',
        ],
        'colors' => [
            'black' => 'Чорны',
            'silver' => 'Срэбны',
            'blue' => 'Сіні',
            'pink' => 'Ружовы',
        ],
    ],
    'settings' => [
        'per_page' => 'Тавараў на старонцы',
        'per_page_hint' => 'Колькі тавараў паказвае старонка раздзела каталога на гэтым сайце. Пуста — як у тэме па змаўчанні.',
    ],
];
