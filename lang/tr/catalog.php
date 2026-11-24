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
        'external_id' => 'Harici kimlik',
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
        'market_name' => 'Yandex Market için mağaza adı',
        'market_name_hint' => 'Yandex Market\'in göstereceği kısa ad, en çok 20 karakter. Boşsa: sitenin adı.',
        'market_company' => 'Şirket',
        'market_company_hint' => 'Satıcı şirketin tam adı. Boşsa: sitenin adı.',
        'market_currency' => 'Fiyatların para birimi',
        'market_currency_hint' => 'Ürün fiyatlarının hangi para biriminde olduğu. Yandex Market RUB, BYN, KZT, UAH, USD ve EUR kabul eder.',
    ],
];
