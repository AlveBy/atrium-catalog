<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use DateInterval;
use DateTimeInterface;
use Generator;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Turns an uploaded CSV or Excel file into the one shape the import reads:
 * UTF-8 CSV, comma-separated, the row's number in the source file first, the
 * header row and empty rows left out. It is read once, row by row, so a large
 * file never sits in memory; the import then resumes from a byte offset.
 *
 * What the file is, is judged by its content, not its name: an Excel file is
 * a zip archive, an old binary .xls and anything else with zero bytes is
 * refused, the rest is CSV. A CSV file's encoding (UTF-8, with or without a
 * BOM, or windows-1251) and its delimiter (semicolon, comma or tab) are
 * recognised by themselves.
 */
final class SourceFile
{
    public const XLSX = 'xlsx';

    public const CSV = 'csv';

    public const UTF8 = 'UTF-8';

    public const WINDOWS_1251 = 'Windows-1251';

    /** The first delimiter wins a tie: a one-column file is read with ";". */
    private const DELIMITERS = [';', ',', "\t"];

    private const BOM = "\xEF\xBB\xBF";

    private const SAMPLE = 65536;

    /**
     * @return array{format:string,encoding:string,delimiter:string,headers:array<int,string>,total:int}
     */
    public function normalize(string $path, string $target): array
    {
        $format = $this->format($path);
        [$rows, $encoding, $delimiter] = $format === self::XLSX ? [$this->xlsx($path), self::UTF8, ''] : $this->csv($path);

        $out = fopen($target, 'wb');
        $headers = null;
        $total = 0;

        foreach ($rows as $number => $cells) {
            $cells = array_map(static fn (mixed $cell): string => trim((string) $cell), $cells);

            if (implode('', $cells) === '') {
                continue;
            }

            if ($headers === null) {
                $headers = $cells;

                continue;
            }

            fputcsv($out, [$number, ...$cells], ',', '"', '');
            $total++;
        }

        fclose($out);

        // Nothing to import below the header either.
        if ($total === 0) {
            throw ImportFailed::because('empty');
        }

        return ['format' => $format, 'encoding' => $encoding, 'delimiter' => $delimiter, 'headers' => $headers, 'total' => $total];
    }

    private function format(string $path): string
    {
        $head = file_get_contents($path, length: 4096);

        if (str_starts_with($head, "PK\x03\x04")) {
            return self::XLSX;
        }

        if (str_contains($head, "\0")) {
            throw ImportFailed::because('format');
        }

        return self::CSV;
    }

    /**
     * @return array{Generator<int,array<int,string|null>>,string,string}
     */
    private function csv(string $path): array
    {
        $handle = fopen($path, 'rb');
        $sample = stream_get_contents($handle, self::SAMPLE);
        $bom = str_starts_with($sample, self::BOM);

        // The sample may end in the middle of a letter: check whole lines.
        $cut = strrpos($sample, "\n");
        $lines = $cut === false ? $sample : substr($sample, 0, $cut);
        $encoding = $bom || mb_check_encoding($lines, self::UTF8) ? self::UTF8 : self::WINDOWS_1251;
        $delimiter = $this->delimiter((string) strtok($sample, "\r\n"));

        fseek($handle, $bom ? strlen(self::BOM) : 0);

        return [$this->csvRows($handle, $delimiter, $encoding), $encoding, $delimiter];
    }

    /**
     * @param  resource  $handle
     * @return Generator<int,array<int,string|null>>
     */
    private function csvRows($handle, string $delimiter, string $encoding): Generator
    {
        $number = 0;

        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $number++;

            yield $number => $encoding === self::UTF8
                ? $cells
                : array_map(static fn (?string $cell): string => mb_convert_encoding((string) $cell, self::UTF8, self::WINDOWS_1251), $cells);
        }

        fclose($handle);
    }

    /**
     * The delimiter that splits the header into the most columns.
     */
    private function delimiter(string $line): string
    {
        $counts = array_map(static fn (string $delimiter): int => count(str_getcsv($line, $delimiter, '"', '')), self::DELIMITERS);

        return self::DELIMITERS[array_keys($counts, max($counts))[0]];
    }

    /**
     * The first sheet, rows numbered as Excel numbers them.
     *
     * @return Generator<int,array<int,string>>
     */
    private function xlsx(string $path): Generator
    {
        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    yield $number => array_map($this->cell(...), $row->toArray());
                }

                return;
            }
        } catch (OpenSpoutException) {
            throw ImportFailed::because('unreadable');
        }
    }

    private function cell(mixed $value): string
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format($value->format('His') === '000000' ? 'Y-m-d' : 'Y-m-d H:i:s'),
            $value instanceof DateInterval => $value->format('%H:%I:%S'),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
