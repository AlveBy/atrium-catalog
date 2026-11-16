<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Ürünler',
        'sections' => 'Katalog bölümleri',
    ],
    'create' => [
        'product' => 'Ürün ekle',
    ],
    'fields' => [
        'article' => 'Ürün kodu',
        'price' => 'Fiyat',
        'brand' => 'Marka',
        'stores' => 'Nereden alınır',
        'label' => 'Etiket',
        'colors' => 'Renkler',
        'released_on' => 'Çıkış tarihi',
        'in_stock' => 'Stokta',
        'features' => 'Öne çıkanlar',
        'related' => 'Bununla iyi gider',
        'specs' => 'Teknik özellikler',
        'package' => 'Kutu içeriği',
        'photos' => 'Fotoğraflar',
        'manual' => 'Kılavuz',
    ],
    'options' => [
        'label' => [
            'new' => 'Yeni',
            'hit' => 'Çok satan',
            'sale' => 'İndirim',
        ],
        'colors' => [
            'black' => 'Siyah',
            'silver' => 'Gümüş',
            'blue' => 'Mavi',
            'pink' => 'Pembe',
        ],
    ],
    'settings' => [
        'per_page' => 'Sayfa başına ürün',
        'per_page_hint' => 'Bu sitede bir katalog bölümünün sayfasında kaç ürün gösterileceği. Boş: temanın varsayılanı.',
    ],
];
