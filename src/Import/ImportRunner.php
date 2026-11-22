<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Catalog\Models\CatalogImport;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Moves a check or an import forward for a while, then saves its place.
 *
 * Whatever drives it — a queue job, the scheduler, the progress page in the
 * browser, the console — calls step() again until it says nothing is left.
 * Only one of them works on an import at a time; one that finds it busy
 * leaves at once.
 */
final class ImportRunner
{
    public function __construct(private readonly Targets $targets, private readonly Hosts $hosts) {}

    /**
     * Work for up to $seconds. True when there is more to do.
     */
    public function step(CatalogImport $import, int $seconds): bool
    {
        $lock = Cache::lock('atrium-catalog-import:'.$import->id, $seconds + 60);

        if (! $lock->get()) {
            return true;
        }

        try {
            $import->refresh();

            return $import->isRunning() && $this->work($import, $seconds);
        } finally {
            $lock->release();
        }
    }

    private function work(CatalogImport $import, int $seconds): bool
    {
        $deadline = now()->addSeconds($seconds);

        try {
            $rows = new RowImporter($import, $this->targets, $this->hosts);

            do {
                foreach (Rows::read($import->path(), $import->position, config('atrium-catalog.import.chunk')) as $row) {
                    $rows->import($row['number'], $row['cells']);
                    $import->position = $row['end'];

                    if (now() >= $deadline) {
                        break;
                    }
                }

                $import->save();
                $done = Rows::finished($import->path(), $import->position);
            } while (! $done && now() < $deadline);
        } catch (Throwable $crash) {
            report($crash);
            $import->stop(CatalogImport::FAILED, __('atrium-catalog::import.failures.crashed'));

            return false;
        }

        if ($done) {
            $this->finish($import);
        }

        return ! $done;
    }

    private function finish(CatalogImport $import): void
    {
        if ($import->status === CatalogImport::CHECKING) {
            $import->stop(CatalogImport::CHECKED);

            return;
        }

        $import->stop(CatalogImport::DONE);
        $import->deleteFile();
    }
}
