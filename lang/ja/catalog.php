<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => '商品',
        'sections' => 'カタログ区分',
    ],
    'create' => [
        'product' => '商品を追加',
    ],
    'fields' => [
        'article' => '品番',
        'external_id' => '外部ID',
        'price' => '価格',
        'brand' => 'ブランド',
        'stores' => '取扱店舗',
        'label' => 'ラベル',
        'colors' => 'カラー',
        'released_on' => '発売日',
        'in_stock' => '在庫あり',
        'features' => '特長',
        'related' => '一緒に購入',
        'specs' => '仕様',
        'package' => '同梱品',
        'photos' => '写真',
        'manual' => '取扱説明書',
    ],
    'options' => [
        'label' => [
            'new' => '新商品',
            'hit' => '人気商品',
            'sale' => 'セール',
        ],
        'colors' => [
            'black' => 'ブラック',
            'silver' => 'シルバー',
            'blue' => 'ブルー',
            'pink' => 'ピンク',
        ],
    ],
    'settings' => [
        'per_page' => '1ページの商品数',
        'per_page_hint' => 'このサイトでカタログのセクションの1ページに表示する商品の数です。空欄の場合はテーマの既定値です。',
    ],
];
