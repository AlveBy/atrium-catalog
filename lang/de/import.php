<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Produkte importieren',
    'permission' => 'Import',

    'index' => [
        'products' => 'Produkte',
        'upload' => 'Datei hochladen',
        'hint' => 'CSV oder Excel (xlsx), bis :size MB. Die erste Zeile enthält die Spaltennamen. Die Kodierung (UTF-8 oder windows-1251) und das Trennzeichen (Semikolon oder Komma) werden automatisch erkannt.',
        'choose' => 'Datei für den Import',
        'submit' => 'Hochladen',
        'history' => 'Frühere Importe',
        'empty' => 'Es wurde noch nichts importiert.',
        'file' => 'Datei',
        'status' => 'Status',
        'result' => 'Ergebnis',
        'counts' => ':created erstellt, :updated aktualisiert, :failed Fehler',
        'user' => 'Von',
        'console' => 'Konsole',
        'date' => 'Wann',
    ],

    'upload' => [
        'file' => 'Datei',
    ],

    'statuses' => [
        'new' => 'Wartet auf die Prüfung',
        'checking' => 'Wird geprüft',
        'checked' => 'Geprüft',
        'importing' => 'Wird importiert',
        'done' => 'Fertig',
        'failed' => 'Fehlgeschlagen',
        'cancelled' => 'Gestoppt',
    ],

    'delimiters' => [
        ';' => 'Semikolon',
        ',' => 'Komma',
        "\t" => 'Tabulator',
    ],

    'show' => [
        'back' => 'Alle Importe',
        'file' => ':format, :encoding, Trennzeichen: :delimiter, :rows Zeilen',
        'workbook' => ':format, :rows Zeilen',
        'checking' => 'Datei wird geprüft…',
        'importing' => 'Import läuft…',
        'progress' => ':processed von :total Zeilen',
        'cancel' => 'Stoppen',
        'cancel_confirm' => 'Stoppen? Was bereits importiert wurde, bleibt erhalten.',
        'report' => 'Bericht',
        'check_report' => 'Prüfung',
        'checked' => 'Die Prüfung ist abgeschlossen: Es wurde noch nichts geschrieben. So wird der Import vorgehen.',
        'done' => 'Der Import ist abgeschlossen.',
        'cancelled' => 'Der Import wurde gestoppt. Was bereits importiert wurde, bleibt erhalten.',
        'counts' => [
            'created' => 'Erstellt',
            'updated' => 'Aktualisiert',
            'skipped' => 'Übersprungen',
            'failed' => 'Fehler',
            'sections' => 'Erstellte Bereiche',
        ],
        'will' => [
            'created' => 'Wird erstellt',
            'updated' => 'Wird aktualisiert',
            'skipped' => 'Wird übersprungen',
            'failed' => 'Fehler',
            'sections' => 'Zu erstellende Bereiche',
        ],
        'row' => 'Zeile',
        'outcome' => 'Ergebnis',
        'reason' => 'Grund',
        'skipped_row' => 'übersprungen',
        'failed_row' => 'Fehler',
        'unlisted' => 'Weitere :count Zeilen sind mitgezählt, aber nicht aufgelistet.',
        'run' => 'Importieren',
        'run_hint' => 'Zeilen mit Fehlern werden ausgelassen; der Rest wird so importiert, wie die Prüfung es gezeigt hat.',
        'mapping' => 'Spalten',
        'mapping_hint' => 'Welches Feld jede Spalte füllt, erraten anhand der Spaltennamen. Eine leere Zelle lässt das Feld unverändert.',
        'column' => 'Spalte',
        'unnamed' => 'Spalte :number',
        'samples' => 'In den ersten Zeilen',
        'target' => 'Feld',
        'skip' => '— nicht importieren —',
        'options' => 'So wird importiert',
        'key_hint' => 'Ein Produkt mit demselben Wert wird aktualisiert, ein fehlendes wird erstellt.',
        'separator_hint' => 'Trennt einen Bereich von dem, in dem er liegt: „Kleidung / Jacken“.',
        'check' => 'Prüfen',
        'check_again' => 'Erneut prüfen',
        'check_hint' => 'Die Prüfung liest die ganze Datei und schreibt nichts: Sie sehen, wie viele Produkte erstellt und aktualisiert würden und in welchen Zeilen Fehler sind.',
    ],

    'settings' => [
        'mapping' => 'Spalten',
        'key' => 'Produkte finden über',
        'mode' => 'Was getan wird',
        'create_sections' => 'Fehlende Bereiche erstellen',
        'separator' => 'Trennzeichen im Bereichspfad',
    ],

    'modes' => [
        'both' => 'Neue Produkte erstellen und vorhandene aktualisieren',
        'create' => 'Nur neue Produkte erstellen',
        'update' => 'Nur vorhandene Produkte aktualisieren',
    ],

    'validation' => [
        'twice' => 'Ein Feld nimmt nur eine Spalte, mehrfach gewählt wurde: :fields.',
        'key' => 'Produkte werden über „:field“ gefunden, aber keine Spalte füllt dieses Feld.',
    ],

    'failures' => [
        'empty' => 'Die Datei enthält keine Zeilen.',
        'format' => 'Das ist weder CSV noch Excel (xlsx). Eine alte .xls-Datei lässt sich in Excel als .xlsx oder CSV speichern.',
        'unreadable' => 'Die Excel-Datei lässt sich nicht lesen: Sie ist möglicherweise beschädigt oder mit einem Passwort geschützt.',
        'crashed' => 'Der Import wurde wegen eines Serverfehlers beendet. Der Fehler wurde protokolliert; was bereits importiert wurde, bleibt erhalten.',
        'missing' => 'Die Datei :file gibt es nicht.',
        'download' => 'Die Datei konnte nicht von :url heruntergeladen werden.',
        'column' => 'Diese Spalte für --map gibt es nicht: „:column“. Schreiben Sie sie so: „Spalte=Feld“.',
    ],

    'problems' => [
        'no_key' => '„:field“ ist leer: Das Produkt kann weder gefunden noch erstellt werden.',
        'exists' => 'Das Produkt „:key“ gibt es schon, und es werden nur neue Produkte erstellt.',
        'missing' => 'Das Produkt „:key“ gibt es nicht, und es werden nur vorhandene Produkte aktualisiert.',
        'database' => 'Die Datenbank hat die Zeile abgelehnt. Der Fehler wurde protokolliert.',
        'not_bool' => '„:field“: „:value“ ist weder Ja noch Nein.',
        'not_option' => '„:field“: Es gibt keine Option „:value“.',
        'ambiguous_option' => '„:field“: Mehr als eine Option heißt „:value“.',
        'section_missing' => 'Es gibt keinen Bereich „:section“.',
        'section_ambiguous' => 'Mehr als ein Bereich heißt „:section“: Geben Sie den Pfad an, „Übergeordnet / :section“.',
        'image_url' => 'Die Bildadresse ist ungültig: :url',
        'image_path' => 'Der Bildpfad ist nicht erlaubt: :path',
        'image_type' => 'Kein Bild (JPEG, PNG, GIF, WebP oder SVG): :path',
        'image_missing' => 'Das Bild :path ist nicht auf der Website vorhanden.',
        'image_host' => 'Das Bild liegt unter einer Adresse im internen Netz, die nicht abgerufen wird: :url',
        'image_download' => 'Das Bild konnte nicht heruntergeladen werden: :url',
        'image_large' => 'Das Bild ist größer als :max KB: :url',
    ],

    'console' => [
        'source' => 'Geben Sie die Datei oder ihre URL an.',
        'like' => 'Einen Import mit der Nummer :id gibt es nicht.',
        'report' => 'Zeilen: :processed. Erstellt: :created, aktualisiert: :updated, übersprungen: :skipped, Fehler: :failed, Bereiche erstellt: :sections.',
        'row' => 'Zeile :row: :messages',
    ],
];
