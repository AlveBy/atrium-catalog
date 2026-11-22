<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => '商品',
        'sections' => '目录分区',
    ],
    'create' => [
        'product' => '添加商品',
    ],
    'fields' => [
        'article' => '货号',
        'external_id' => '外部编码',
        'price' => '价格',
        'brand' => '品牌',
        'stores' => '购买门店',
        'label' => '标签',
        'colors' => '颜色',
        'released_on' => '上市日期',
        'in_stock' => '有货',
        'features' => '特点',
        'related' => '搭配推荐',
        'specs' => '规格参数',
        'package' => '包装清单',
        'photos' => '照片',
        'manual' => '说明书',
    ],
    'options' => [
        'label' => [
            'new' => '新品',
            'hit' => '热销',
            'sale' => '特价',
        ],
        'colors' => [
            'black' => '黑色',
            'silver' => '银色',
            'blue' => '蓝色',
            'pink' => '粉色',
        ],
    ],
    'settings' => [
        'per_page' => '每页商品数',
        'per_page_hint' => '本站目录分区每页显示的商品数量。留空则使用主题的默认值。',
    ],
];
