<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Importer des produits',
    'permission' => 'Import',

    'index' => [
        'products' => 'Produits',
        'upload' => 'Téléverser un fichier',
        'hint' => 'CSV ou Excel (xlsx), jusqu’à :size Mo. La première ligne contient les noms des colonnes. L’encodage (UTF-8 ou windows-1251) et le séparateur (point-virgule ou virgule) sont reconnus automatiquement.',
        'choose' => 'Fichier à importer',
        'submit' => 'Téléverser',
        'history' => 'Imports précédents',
        'empty' => 'Rien n’a encore été importé.',
        'file' => 'Fichier',
        'status' => 'Statut',
        'result' => 'Résultat',
        'counts' => ':created créés, :updated mis à jour, :failed erreurs',
        'user' => 'Qui',
        'console' => 'console',
        'date' => 'Quand',
    ],

    'upload' => [
        'file' => 'fichier',
    ],

    'statuses' => [
        'new' => 'En attente de vérification',
        'checking' => 'Vérification en cours',
        'checked' => 'Vérifié',
        'importing' => 'Import en cours',
        'done' => 'Terminé',
        'failed' => 'Échec',
        'cancelled' => 'Arrêté',
    ],

    'delimiters' => [
        ';' => 'point-virgule',
        ',' => 'virgule',
        "\t" => 'tabulation',
    ],

    'show' => [
        'back' => 'Tous les imports',
        'file' => ':format, :encoding, séparateur : :delimiter, :rows lignes',
        'workbook' => ':format, :rows lignes',
        'checking' => 'Vérification du fichier…',
        'importing' => 'Import en cours…',
        'progress' => ':processed lignes sur :total',
        'cancel' => 'Arrêter',
        'cancel_confirm' => 'Arrêter ? Ce qui a déjà été importé est conservé.',
        'report' => 'Rapport',
        'check_report' => 'Vérification',
        'checked' => 'La vérification est terminée : rien n’a encore été écrit. Voici ce que fera l’import.',
        'done' => 'L’import est terminé.',
        'cancelled' => 'L’import a été arrêté. Ce qui avait déjà été importé est conservé.',
        'counts' => [
            'created' => 'Créés',
            'updated' => 'Mis à jour',
            'skipped' => 'Ignorés',
            'failed' => 'Erreurs',
            'sections' => 'Rubriques créées',
        ],
        'will' => [
            'created' => 'Seront créés',
            'updated' => 'Seront mis à jour',
            'skipped' => 'Seront ignorés',
            'failed' => 'Erreurs',
            'sections' => 'Rubriques à créer',
        ],
        'row' => 'Ligne',
        'outcome' => 'Résultat',
        'reason' => 'Raison',
        'skipped_row' => 'ignorée',
        'failed_row' => 'erreur',
        'unlisted' => ':count lignes de plus sont comptées mais ne sont pas listées.',
        'run' => 'Importer',
        'run_hint' => 'Les lignes en erreur sont écartées ; le reste est importé comme l’a montré la vérification.',
        'mapping' => 'Colonnes',
        'mapping_hint' => 'Le champ que remplit chaque colonne, deviné d’après les noms des colonnes. Une cellule vide laisse le champ tel quel.',
        'column' => 'Colonne',
        'unnamed' => 'Colonne :number',
        'samples' => 'Dans les premières lignes',
        'target' => 'Champ',
        'skip' => '— ne pas importer —',
        'options' => 'Comment importer',
        'key_hint' => 'Un produit ayant la même valeur est mis à jour ; s’il n’existe pas, il est créé.',
        'separator_hint' => 'Sépare une rubrique de celle qui la contient : « Vêtements / Vestes ».',
        'check' => 'Vérifier',
        'check_again' => 'Vérifier à nouveau',
        'check_hint' => 'La vérification lit tout le fichier et n’écrit rien : vous verrez combien de produits seraient créés et mis à jour, et quelles lignes contiennent des erreurs.',
    ],

    'settings' => [
        'mapping' => 'Colonnes',
        'key' => 'Trouver les produits par',
        'mode' => 'Que faire',
        'create_sections' => 'Créer les rubriques qui n’existent pas',
        'separator' => 'Séparateur du chemin de rubrique',
    ],

    'modes' => [
        'both' => 'Créer les nouveaux produits et mettre à jour les existants',
        'create' => 'Créer uniquement les nouveaux produits',
        'update' => 'Mettre à jour uniquement les produits existants',
    ],

    'validation' => [
        'twice' => 'Un champ n’accepte qu’une seule colonne ; choisi plusieurs fois : :fields.',
        'key' => 'Les produits sont trouvés par « :field », mais aucune colonne ne le remplit.',
    ],

    'failures' => [
        'empty' => 'Le fichier ne contient aucune ligne.',
        'format' => 'Ce n’est ni un CSV ni un Excel (xlsx). Un ancien fichier .xls peut être enregistré en .xlsx ou en CSV depuis Excel.',
        'unreadable' => 'Le fichier Excel est illisible : il est peut-être endommagé ou protégé par un mot de passe.',
        'crashed' => 'L’import s’est arrêté à cause d’une erreur du serveur. Elle a été consignée ; ce qui avait déjà été importé est conservé.',
        'missing' => 'Le fichier :file n’existe pas.',
        'download' => 'Le fichier n’a pas pu être téléchargé depuis :url.',
        'column' => 'Aucune colonne de ce nom pour --map : « :column ». Écrivez-la ainsi : « Colonne=champ ».',
    ],

    'problems' => [
        'no_key' => '« :field » est vide : le produit ne peut être ni trouvé ni créé.',
        'exists' => 'Le produit « :key » existe déjà, et seuls les nouveaux produits sont créés.',
        'missing' => 'Le produit « :key » n’existe pas, et seuls les produits existants sont mis à jour.',
        'database' => 'La base de données a refusé la ligne. L’erreur a été consignée.',
        'not_bool' => '« :field » : « :value » n’est ni oui ni non.',
        'not_option' => '« :field » : l’option « :value » n’existe pas.',
        'ambiguous_option' => '« :field » : plusieurs options portent le nom « :value ».',
        'section_missing' => 'La rubrique « :section » n’existe pas.',
        'section_ambiguous' => 'Plusieurs rubriques portent le nom « :section » : indiquez son chemin, « Parent / :section ».',
        'image_url' => 'L’adresse de l’image n’est pas valide : :url',
        'image_path' => 'Le chemin de l’image n’est pas autorisé : :path',
        'image_type' => 'Ce n’est pas une image (JPEG, PNG, GIF, WebP ou SVG) : :path',
        'image_missing' => 'L’image :path n’existe pas sur le site.',
        'image_host' => 'L’image se trouve à une adresse du réseau interne, qui n’est pas récupérée : :url',
        'image_download' => 'L’image n’a pas pu être téléchargée : :url',
        'image_large' => 'L’image dépasse :max Ko : :url',
    ],

    'console' => [
        'source' => 'Indiquez le fichier ou son URL.',
        'like' => 'L’import numéro :id n’existe pas.',
        'report' => 'Lignes : :processed. Créés : :created, mis à jour : :updated, ignorés : :skipped, erreurs : :failed, rubriques créées : :sections.',
        'row' => 'Ligne :row : :messages',
    ],
];
