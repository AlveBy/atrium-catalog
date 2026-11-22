<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Import produktów',
    'permission' => 'Import',

    'index' => [
        'products' => 'Produkty',
        'upload' => 'Prześlij plik',
        'hint' => 'CSV lub Excel (xlsx), do :size MB. Pierwszy wiersz zawiera nazwy kolumn. Kodowanie (UTF-8 lub windows-1251) i separator (średnik lub przecinek) są rozpoznawane automatycznie.',
        'choose' => 'Plik do importu',
        'submit' => 'Prześlij',
        'history' => 'Poprzednie importy',
        'empty' => 'Nic jeszcze nie zaimportowano.',
        'file' => 'Plik',
        'status' => 'Stan',
        'result' => 'Wynik',
        'counts' => 'utworzono :created, zaktualizowano :updated, błędów :failed',
        'user' => 'Kto',
        'console' => 'konsola',
        'date' => 'Kiedy',
    ],

    'upload' => [
        'file' => 'plik',
    ],

    'statuses' => [
        'new' => 'Czeka na sprawdzenie',
        'checking' => 'Sprawdzanie',
        'checked' => 'Sprawdzony',
        'importing' => 'Importowanie',
        'done' => 'Gotowe',
        'failed' => 'Niepowodzenie',
        'cancelled' => 'Zatrzymany',
    ],

    'delimiters' => [
        ';' => 'średnik',
        ',' => 'przecinek',
        "\t" => 'tabulator',
    ],

    'show' => [
        'back' => 'Wszystkie importy',
        'file' => ':format, :encoding, separator: :delimiter, wierszy: :rows',
        'workbook' => ':format, wierszy: :rows',
        'checking' => 'Sprawdzanie pliku…',
        'importing' => 'Importowanie…',
        'progress' => ':processed z :total wierszy',
        'cancel' => 'Zatrzymaj',
        'cancel_confirm' => 'Zatrzymać? To, co już zaimportowano, zostanie.',
        'report' => 'Raport',
        'check_report' => 'Sprawdzenie',
        'checked' => 'Sprawdzanie zakończone: nic jeszcze nie zapisano. Oto, co zrobi import.',
        'done' => 'Import zakończony.',
        'cancelled' => 'Import został zatrzymany. To, co już zaimportowano, zostało.',
        'counts' => [
            'created' => 'Utworzono',
            'updated' => 'Zaktualizowano',
            'skipped' => 'Pominięto',
            'failed' => 'Błędy',
            'sections' => 'Utworzone działy',
        ],
        'will' => [
            'created' => 'Zostanie utworzonych',
            'updated' => 'Zostanie zaktualizowanych',
            'skipped' => 'Zostanie pominiętych',
            'failed' => 'Błędy',
            'sections' => 'Działy do utworzenia',
        ],
        'row' => 'Wiersz',
        'outcome' => 'Wynik',
        'reason' => 'Powód',
        'skipped_row' => 'pominięty',
        'failed_row' => 'błąd',
        'unlisted' => 'Jeszcze :count wierszy jest policzonych, ale nie ma ich na liście.',
        'run' => 'Importuj',
        'run_hint' => 'Wiersze z błędami zostaną pominięte; reszta zostanie zaimportowana tak, jak pokazało sprawdzenie.',
        'mapping' => 'Kolumny',
        'mapping_hint' => 'Które pole wypełnia każda kolumna, odgadnięte po nazwach kolumn. Pusta komórka zostawia pole bez zmian.',
        'column' => 'Kolumna',
        'unnamed' => 'Kolumna :number',
        'samples' => 'W pierwszych wierszach',
        'target' => 'Pole',
        'skip' => '— nie importuj —',
        'options' => 'Jak importować',
        'key_hint' => 'Produkt o tej samej wartości zostanie zaktualizowany, a brakujący — utworzony.',
        'separator_hint' => 'Oddziela dział od działu, w którym się znajduje: „Odzież / Kurtki”.',
        'check' => 'Sprawdź',
        'check_again' => 'Sprawdź ponownie',
        'check_hint' => 'Sprawdzenie czyta cały plik i niczego nie zapisuje: zobaczysz, ile produktów zostałoby utworzonych i zaktualizowanych oraz w których wierszach są błędy.',
    ],

    'settings' => [
        'mapping' => 'Kolumny',
        'key' => 'Znajdź produkty według',
        'mode' => 'Co zrobić',
        'create_sections' => 'Utwórz brakujące działy',
        'separator' => 'Separator ścieżki działu',
    ],

    'modes' => [
        'both' => 'Twórz nowe produkty i aktualizuj istniejące',
        'create' => 'Tylko twórz nowe produkty',
        'update' => 'Tylko aktualizuj istniejące produkty',
    ],

    'validation' => [
        'twice' => 'Pole przyjmuje tylko jedną kolumnę; wybrano więcej niż raz: :fields.',
        'key' => 'Produkty są wyszukiwane według „:field”, ale żadna kolumna go nie wypełnia.',
    ],

    'failures' => [
        'empty' => 'Plik nie ma wierszy.',
        'format' => 'To nie jest ani CSV, ani Excel (xlsx). Stary plik .xls można zapisać w Excelu jako .xlsx lub CSV.',
        'unreadable' => 'Nie można odczytać pliku Excel: może być uszkodzony lub chroniony hasłem.',
        'crashed' => 'Import zatrzymał się z powodu błędu na serwerze. Błąd został zapisany w dzienniku; to, co już zaimportowano, zostało.',
        'missing' => 'Nie ma pliku :file.',
        'download' => 'Nie udało się pobrać pliku z :url.',
        'column' => 'Nie ma takiej kolumny dla --map: „:column”. Zapisz ją tak: „Kolumna=pole”.',
    ],

    'problems' => [
        'no_key' => '„:field” jest puste: nie można znaleźć ani utworzyć produktu.',
        'exists' => 'Produkt „:key” już istnieje, a tworzone są tylko nowe.',
        'missing' => 'Produktu „:key” nie ma, a aktualizowane są tylko istniejące.',
        'database' => 'Baza danych odrzuciła wiersz. Błąd został zapisany w dzienniku.',
        'not_bool' => '„:field”: „:value” to ani tak, ani nie.',
        'not_option' => '„:field”: nie ma opcji „:value”.',
        'ambiguous_option' => '„:field”: więcej niż jedna opcja nazywa się „:value”.',
        'section_missing' => 'Nie ma działu „:section”.',
        'section_ambiguous' => 'Więcej niż jeden dział nazywa się „:section”: podaj jego ścieżkę, „Nadrzędny / :section”.',
        'image_url' => 'Adres obrazu jest nieprawidłowy: :url',
        'image_path' => 'Ścieżka obrazu jest niedozwolona: :path',
        'image_type' => 'To nie jest obraz (JPEG, PNG, GIF, WebP lub SVG): :path',
        'image_missing' => 'Obrazu :path nie ma w witrynie.',
        'image_host' => 'Obraz znajduje się pod adresem wewnątrz sieci, który nie jest pobierany: :url',
        'image_download' => 'Nie udało się pobrać obrazu: :url',
        'image_large' => 'Obraz jest większy niż :max KB: :url',
    ],

    'console' => [
        'source' => 'Podaj plik lub jego adres URL.',
        'like' => 'Nie ma importu o numerze :id.',
        'report' => 'Wiersze: :processed. Utworzono: :created, zaktualizowano: :updated, pominięto: :skipped, błędów: :failed, utworzonych działów: :sections.',
        'row' => 'Wiersz :row: :messages',
    ],
];
