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
        'external_id' => 'Внешний код',
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
        'market_name' => 'Название магазина для Яндекс Маркета',
        'market_name_hint' => 'Короткое название, которое покажет Яндекс Маркет, до 20 символов. Пусто — название сайта.',
        'market_company' => 'Компания',
        'market_company_hint' => 'Полное название компании-продавца. Пусто — название сайта.',
        'market_currency' => 'Валюта цен',
        'market_currency_hint' => 'В какой валюте указаны цены товаров. Яндекс Маркет принимает RUB, BYN, KZT, UAH, USD и EUR.',
    ],
];
