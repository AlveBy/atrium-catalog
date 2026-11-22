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
    ],
];
