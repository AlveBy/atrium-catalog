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
    ],
];
