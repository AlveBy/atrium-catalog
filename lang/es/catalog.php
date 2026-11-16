<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'products' => 'Productos',
        'sections' => 'Secciones del catálogo',
    ],
    'create' => [
        'product' => 'Añadir producto',
    ],
    'fields' => [
        'article' => 'Referencia',
        'price' => 'Precio',
        'brand' => 'Marca',
        'stores' => 'Dónde comprar',
        'label' => 'Etiqueta',
        'colors' => 'Colores',
        'released_on' => 'Fecha de lanzamiento',
        'in_stock' => 'En stock',
        'features' => 'Características destacadas',
        'related' => 'Combina con',
        'specs' => 'Especificaciones',
        'package' => 'Contenido de la caja',
        'photos' => 'Fotos',
        'manual' => 'Manual',
    ],
    'options' => [
        'label' => [
            'new' => 'Novedad',
            'hit' => 'Más vendido',
            'sale' => 'Oferta',
        ],
        'colors' => [
            'black' => 'Negro',
            'silver' => 'Plata',
            'blue' => 'Azul',
            'pink' => 'Rosa',
        ],
    ],
    'settings' => [
        'per_page' => 'Productos por página',
        'per_page_hint' => 'Cuántos productos muestra una página de una sección del catálogo en este sitio. Vacío: el valor por defecto del tema.',
    ],
];
