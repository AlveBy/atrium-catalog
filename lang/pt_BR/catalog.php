<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Produtos',
        'sections' => 'Seções do catálogo',
    ],
    'create' => [
        'product' => 'Adicionar produto',
    ],
    'fields' => [
        'article' => 'Código do produto',
        'external_id' => 'ID externo',
        'price' => 'Preço',
        'brand' => 'Marca',
        'stores' => 'Onde comprar',
        'label' => 'Etiqueta',
        'colors' => 'Cores',
        'released_on' => 'Data de lançamento',
        'in_stock' => 'Em estoque',
        'features' => 'Destaques',
        'related' => 'Combina com',
        'specs' => 'Especificações',
        'package' => 'Conteúdo da embalagem',
        'photos' => 'Fotos',
        'manual' => 'Manual',
    ],
    'options' => [
        'label' => [
            'new' => 'Novidade',
            'hit' => 'Mais vendido',
            'sale' => 'Promoção',
        ],
        'colors' => [
            'black' => 'Preto',
            'silver' => 'Prata',
            'blue' => 'Azul',
            'pink' => 'Rosa',
        ],
    ],
    'settings' => [
        'per_page' => 'Produtos por página',
        'per_page_hint' => 'Quantos produtos uma página de uma seção do catálogo mostra neste site. Vazio: o padrão do tema.',
        'market_name' => 'Nome da loja para o Yandex Market',
        'market_name_hint' => 'O nome curto que o Yandex Market mostra, até 20 caracteres. Vazio: o nome do site.',
        'market_company' => 'Empresa',
        'market_company_hint' => 'O nome completo da empresa que vende. Vazio: o nome do site.',
        'market_currency' => 'Moeda dos preços',
        'market_currency_hint' => 'A moeda dos preços dos produtos. O Yandex Market aceita RUB, BYN, KZT, UAH, USD e EUR.',
    ],
];
