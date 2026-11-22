<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Import products',
    'permission' => 'Import',

    'index' => [
        'products' => 'Products',
        'upload' => 'Upload a file',
        'hint' => 'CSV or Excel (xlsx), up to :size MB. The first row holds the column names. The encoding (UTF-8 or windows-1251) and the delimiter (semicolon or comma) are recognised by themselves.',
        'choose' => 'File to import',
        'submit' => 'Upload',
        'history' => 'Earlier imports',
        'empty' => 'Nothing has been imported yet.',
        'file' => 'File',
        'status' => 'Status',
        'result' => 'Result',
        'counts' => 'created :created, updated :updated, errors :failed',
        'user' => 'Who',
        'console' => 'console',
        'date' => 'When',
    ],

    'upload' => [
        'file' => 'file',
    ],

    'statuses' => [
        'new' => 'Waiting for the check',
        'checking' => 'Checking',
        'checked' => 'Checked',
        'importing' => 'Importing',
        'done' => 'Done',
        'failed' => 'Failed',
        'cancelled' => 'Stopped',
    ],

    'delimiters' => [
        ';' => 'semicolon',
        ',' => 'comma',
        "\t" => 'tab',
    ],

    'show' => [
        'back' => 'All imports',
        'file' => ':format, :encoding, delimiter: :delimiter, :rows rows',
        'workbook' => ':format, :rows rows',
        'checking' => 'Checking the file…',
        'importing' => 'Importing…',
        'progress' => ':processed of :total rows',
        'cancel' => 'Stop',
        'cancel_confirm' => 'Stop it? What has been imported stays.',
        'report' => 'Report',
        'check_report' => 'Check',
        'checked' => 'The check is over: nothing has been written yet. This is what the import will do.',
        'done' => 'The import is over.',
        'cancelled' => 'The import was stopped. What had been imported stays.',
        'counts' => [
            'created' => 'Created',
            'updated' => 'Updated',
            'skipped' => 'Skipped',
            'failed' => 'Errors',
            'sections' => 'Sections created',
        ],
        'will' => [
            'created' => 'Will be created',
            'updated' => 'Will be updated',
            'skipped' => 'Will be skipped',
            'failed' => 'Errors',
            'sections' => 'Sections to create',
        ],
        'row' => 'Row',
        'outcome' => 'Outcome',
        'reason' => 'Why',
        'skipped_row' => 'skipped',
        'failed_row' => 'error',
        'unlisted' => ':count more rows are counted but not listed.',
        'run' => 'Import',
        'run_hint' => 'Rows with errors are left out; the rest is imported as the check showed.',
        'mapping' => 'Columns',
        'mapping_hint' => 'Which field each column fills, guessed from the column names. An empty cell leaves the field as it is.',
        'column' => 'Column',
        'unnamed' => 'Column :number',
        'samples' => 'In the first rows',
        'target' => 'Field',
        'skip' => '— do not import —',
        'options' => 'How to import',
        'key_hint' => 'A product with the same value is updated; one that is not there is created.',
        'separator_hint' => 'Separates a section from the one it is in: "Clothes / Jackets".',
        'check' => 'Check',
        'check_again' => 'Check again',
        'check_hint' => 'The check reads the whole file and writes nothing: you will see how many products would be created and updated and which rows have errors.',
    ],

    'settings' => [
        'mapping' => 'Columns',
        'key' => 'Find products by',
        'mode' => 'What to do',
        'create_sections' => 'Create the sections that are not there',
        'separator' => 'Section path separator',
    ],

    'modes' => [
        'both' => 'Create new products and update existing ones',
        'create' => 'Only create new products',
        'update' => 'Only update existing products',
    ],

    'validation' => [
        'twice' => 'A field takes one column only; chosen more than once: :fields.',
        'key' => 'Products are found by ":field", but no column fills it.',
    ],

    'failures' => [
        'empty' => 'The file has no rows.',
        'format' => 'This is not a CSV or Excel (xlsx) file. An old .xls file can be saved as .xlsx or CSV in Excel.',
        'unreadable' => 'The Excel file cannot be read: it may be damaged or protected with a password.',
        'crashed' => 'The import stopped because of an error on the server. It has been logged; what had been imported stays.',
        'missing' => 'There is no file :file.',
        'download' => 'The file could not be downloaded from :url.',
        'column' => 'No such column for --map: ":column". Write it as "Column=field".',
    ],

    'problems' => [
        'no_key' => '":field" is empty: the product cannot be found or created.',
        'exists' => 'The product ":key" is already there, and only new products are created.',
        'missing' => 'There is no product ":key", and only existing products are updated.',
        'database' => 'The database refused the row. The error has been logged.',
        'not_bool' => '":field": ":value" is neither yes nor no.',
        'not_option' => '":field": there is no option ":value".',
        'ambiguous_option' => '":field": more than one option is called ":value".',
        'section_missing' => 'There is no section ":section".',
        'section_ambiguous' => 'More than one section is called ":section": write its path, "Parent / :section".',
        'image_url' => 'The image address is not valid: :url',
        'image_path' => 'The image path is not allowed: :path',
        'image_type' => 'Not an image (JPEG, PNG, GIF, WebP or SVG): :path',
        'image_missing' => 'There is no image :path on the site.',
        'image_host' => 'The image is on an address inside the network, which is not fetched: :url',
        'image_download' => 'The image could not be downloaded: :url',
        'image_large' => 'The image is larger than :max KB: :url',
    ],

    'console' => [
        'source' => 'Name the file or its URL.',
        'like' => 'There is no import number :id.',
        'report' => 'Rows: :processed. Created: :created, updated: :updated, skipped: :skipped, errors: :failed, sections created: :sections.',
        'row' => 'Row :row: :messages',
    ],
];
