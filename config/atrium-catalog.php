<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The catalog module. Publish with
 * `php artisan vendor:publish --tag=atrium-catalog-config`.
 */
return [
    'import' => [
        // Where uploaded files wait for their import (read as they were left by
        // the upload, so a local folder).
        'directory' => env('ATRIUM_CATALOG_IMPORT_DIRECTORY', storage_path('app/private/atrium-catalog-imports')),

        // The largest file the panel accepts, in kilobytes.
        'max_size' => 51200,

        // Rows read from the file at a time.
        'chunk' => 100,

        // How long one step works before it saves its place and lets go: a
        // queue job, a run of the scheduler and the progress page each work in
        // steps, so a large file never meets a time limit.
        'seconds' => 20,
        'request_seconds' => 5,

        // How many rows with a problem the report lists (all are counted).
        'errors' => 500,

        // Images by URL: how long to wait for one and how large it may be (KB).
        'image_timeout' => 10,
        'image_max_size' => 10240,
    ],
];
