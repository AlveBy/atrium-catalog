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
        'external_id' => 'Зовнішній код',
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
        'market_name' => 'Назва магазину для Яндекс Маркету',
        'market_name_hint' => 'Коротка назва, яку покаже Яндекс Маркет, до 20 символів. Порожньо — назва сайту.',
        'market_company' => 'Компанія',
        'market_company_hint' => 'Повна назва компанії-продавця. Порожньо — назва сайту.',
        'market_currency' => 'Валюта цін',
        'market_currency_hint' => 'У якій валюті вказано ціни товарів. Яндекс Маркет приймає RUB, BYN, KZT, UAH, USD і EUR.',
    ],
];
