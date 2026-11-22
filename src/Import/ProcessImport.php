<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Catalog\Models\CatalogImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * A step of a check or an import for a queue worker; it queues the next step
 * itself until nothing is left. Without a queue (QUEUE_CONNECTION=sync) the
 * progress page and the scheduler move the import instead.
 */
final class ProcessImport implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public readonly int $import) {}

    /**
     * Whether a queue worker moves imports.
     */
    public static function queued(): bool
    {
        return config('queue.default') !== 'sync';
    }

    public static function start(CatalogImport $import): void
    {
        if (self::queued()) {
            self::dispatch($import->id);
        }
    }

    public function handle(ImportRunner $runner): void
    {
        $import = CatalogImport::query()->find($this->import);

        // Another step may hold the import for a while: try again shortly.
        if ($import !== null && $runner->step($import, config('atrium-catalog.import.seconds'))) {
            self::dispatch($this->import)->delay(1);
        }
    }
}
