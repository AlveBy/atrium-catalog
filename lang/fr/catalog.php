<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Produits',
        'sections' => 'Rubriques du catalogue',
    ],
    'create' => [
        'product' => 'Ajouter un produit',
    ],
    'fields' => [
        'article' => 'Référence',
        'external_id' => 'ID externe',
        'price' => 'Prix',
        'brand' => 'Marque',
        'stores' => 'Où acheter',
        'label' => 'Étiquette',
        'colors' => 'Couleurs',
        'released_on' => 'Date de sortie',
        'in_stock' => 'En stock',
        'features' => 'Points forts',
        'related' => 'Va bien avec',
        'specs' => 'Caractéristiques',
        'package' => 'Contenu de la boîte',
        'photos' => 'Photos',
        'manual' => 'Mode d’emploi',
    ],
    'options' => [
        'label' => [
            'new' => 'Nouveau',
            'hit' => 'Meilleure vente',
            'sale' => 'Promotion',
        ],
        'colors' => [
            'black' => 'Noir',
            'silver' => 'Argent',
            'blue' => 'Bleu',
            'pink' => 'Rose',
        ],
    ],
    'settings' => [
        'per_page' => 'Produits par page',
        'per_page_hint' => 'Combien de produits une page de rubrique du catalogue affiche sur ce site. Vide : la valeur par défaut du thème.',
        'market_name' => 'Nom de la boutique pour Yandex Market',
        'market_name_hint' => 'Le nom court affiché par Yandex Market, 20 caractères au plus. Vide : le nom du site.',
        'market_company' => 'Société',
        'market_company_hint' => 'Le nom complet de la société qui vend. Vide : le nom du site.',
        'market_currency' => 'Devise des prix',
        'market_currency_hint' => 'La devise des prix des produits. Yandex Market accepte RUB, BYN, KZT, UAH, USD et EUR.',
    ],
];
