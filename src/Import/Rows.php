<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

/**
 * Reads the file SourceFile wrote, a piece at a time: from a byte offset, so
 * the next piece starts where the last one stopped without reading the file
 * from the top again. Each row says where it ends, so an import can stop
 * after any row and go on from there.
 */
final class Rows
{
    /**
     * @return array<int,array{number:int,cells:array<int,string>,end:int}>
     */
    public static function read(string $file, int $position, int $limit): array
    {
        $handle = fopen($file, 'rb');
        fseek($handle, $position);
        $rows = [];

        while (count($rows) < $limit && ($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $number = (int) array_shift($cells);
            $rows[] = ['number' => $number, 'cells' => $cells, 'end' => ftell($handle)];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Whether nothing is left after this offset.
     */
    public static function finished(string $file, int $position): bool
    {
        return $position >= filesize($file);
    }

    /**
     * The first rows, for the mapping screen.
     *
     * @return array<int,array<int,string>>
     */
    public static function preview(string $file, int $limit): array
    {
        return array_column(self::read($file, 0, $limit), 'cells');
    }
}
