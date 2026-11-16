<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Товари',
        'sections' => 'Розділи каталогу',
    ],
    'create' => [
        'product' => 'Додати товар',
    ],
    'fields' => [
        'article' => 'Артикул',
        'price' => 'Ціна',
        'brand' => 'Бренд',
        'stores' => 'Де купити',
        'label' => 'Мітка',
        'colors' => 'Кольори',
        'released_on' => 'Дата виходу',
        'in_stock' => 'В наявності',
        'features' => 'Особливості',
        'related' => 'З цим купують',
        'specs' => 'Характеристики',
        'package' => 'Комплектація',
        'photos' => 'Фотографії',
        'manual' => 'Інструкція',
    ],
    'options' => [
        'label' => [
            'new' => 'Новинка',
            'hit' => 'Хіт продажів',
            'sale' => 'Розпродаж',
        ],
        'colors' => [
            'black' => 'Чорний',
            'silver' => 'Сріблястий',
            'blue' => 'Синій',
            'pink' => 'Рожевий',
        ],
    ],
    'settings' => [
        'per_page' => 'Товарів на сторінці',
        'per_page_hint' => 'Скільки товарів показує сторінка розділу каталогу на цьому сайті. Порожньо — як у темі за замовчуванням.',
    ],
];
