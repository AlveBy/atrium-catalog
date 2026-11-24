<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Prodotti',
        'sections' => 'Sezioni del catalogo',
    ],
    'create' => [
        'product' => 'Aggiungi prodotto',
    ],
    'fields' => [
        'article' => 'Codice articolo',
        'external_id' => 'ID esterno',
        'price' => 'Prezzo',
        'brand' => 'Marchio',
        'stores' => 'Dove acquistare',
        'label' => 'Etichetta',
        'colors' => 'Colori',
        'released_on' => 'Data di uscita',
        'in_stock' => 'Disponibile',
        'features' => 'Caratteristiche',
        'related' => 'Si abbina con',
        'specs' => 'Specifiche tecniche',
        'package' => 'Contenuto della confezione',
        'photos' => 'Foto',
        'manual' => 'Manuale',
    ],
    'options' => [
        'label' => [
            'new' => 'Novità',
            'hit' => 'Più venduto',
            'sale' => 'Saldi',
        ],
        'colors' => [
            'black' => 'Nero',
            'silver' => 'Argento',
            'blue' => 'Blu',
            'pink' => 'Rosa',
        ],
    ],
    'settings' => [
        'per_page' => 'Prodotti per pagina',
        'per_page_hint' => 'Quanti prodotti mostra una pagina di una sezione del catalogo su questo sito. Vuoto: il valore predefinito del tema.',
        'market_name' => 'Nome del negozio per Yandex Market',
        'market_name_hint' => 'Il nome breve che mostra Yandex Market, fino a 20 caratteri. Vuoto: il nome del sito.',
        'market_company' => 'Azienda',
        'market_company_hint' => 'Il nome completo dell\'azienda che vende. Vuoto: il nome del sito.',
        'market_currency' => 'Valuta dei prezzi',
        'market_currency_hint' => 'La valuta dei prezzi dei prodotti. Yandex Market accetta RUB, BYN, KZT, UAH, USD ed EUR.',
    ],
];
