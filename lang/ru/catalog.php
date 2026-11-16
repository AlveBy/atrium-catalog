<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Товары',
        'sections' => 'Разделы каталога',
    ],
    'create' => [
        'product' => 'Добавить товар',
    ],
    'fields' => [
        'article' => 'Артикул',
        'price' => 'Цена',
        'brand' => 'Бренд',
        'stores' => 'Где купить',
        'label' => 'Метка',
        'colors' => 'Цвета',
        'released_on' => 'Дата выхода',
        'in_stock' => 'В наличии',
        'features' => 'Особенности',
        'related' => 'С этим берут',
        'specs' => 'Характеристики',
        'package' => 'Комплектация',
        'photos' => 'Фотографии',
        'manual' => 'Инструкция',
    ],
    'options' => [
        'label' => [
            'new' => 'Новинка',
            'hit' => 'Хит продаж',
            'sale' => 'Распродажа',
        ],
        'colors' => [
            'black' => 'Чёрный',
            'silver' => 'Серебристый',
            'blue' => 'Синий',
            'pink' => 'Розовый',
        ],
    ],
    'settings' => [
        'per_page' => 'Товаров на странице',
        'per_page_hint' => 'Сколько товаров показывает страница раздела каталога на этом сайте. Пусто — как в теме по умолчанию.',
    ],
];
