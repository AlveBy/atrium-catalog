<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Transfer;

use Atrium\Admin\Models\DataImport;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Import and export of the products through the generic mechanism of the
 * admin: the console commands and the admin routes of the module
 * `catalog_products`.
 */
abstract class ProductTransferTestCase extends TestCase
{
    protected string $directory;

    protected string $files;

    protected string $disk;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.import.directory', $this->directory = sys_get_temp_dir().'/atrium-import-'.Str::random(8));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = sys_get_temp_dir().'/atrium-import-files-'.Str::random(8);
        File::ensureDirectoryExists($this->files);
        // The public disk of the test alone: Storage::fake() shares one folder with every test that runs at the same time.
        File::ensureDirectoryExists($this->disk = sys_get_temp_dir().'/atrium-import-disk-'.Str::random(8));
        Storage::set('public', Storage::build(['driver' => 'local', 'root' => $this->disk, 'url' => '/storage']));
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        File::deleteDirectory($this->files);
        File::deleteDirectory($this->disk);

        parent::tearDown();
    }

    protected function file(string $name, string $content): string
    {
        file_put_contents($path = $this->files.'/'.$name, $content);

        return $path;
    }

    /**
     * A table as a file of the format: the first row is the header.
     *
     * @param  array<int,array<int,string>>  $rows
     */
    protected function table(string $format, array $rows): string
    {
        if ($format === 'csv') {
            return $this->file('table.csv', implode("\n", array_map(static fn (array $row): string => implode(';', $row), $rows))."\n");
        }

        $writer = new Writer;
        $writer->openToFile($path = $this->files.'/table.xlsx');

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    /**
     * The import of the file, run to the end from the console.
     *
     * @param  array<string,mixed>  $arguments
     */
    protected function importFile(string $path, array $arguments = []): DataImport
    {
        $this->artisan('atrium:import', ['module' => 'catalog_products', 'source' => $path, ...$arguments]);

        return DataImport::query()->latest('id')->firstOrFail();
    }

    /**
     * The rows of an exported file, each as header => cell.
     *
     * @return array<int,array<string,string>>
     */
    protected function read(string $path): array
    {
        $rows = [];

        if (str_ends_with($path, '.xlsx')) {
            $reader = new Reader;
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map(static fn (mixed $cell): string => (string) $cell, $row->toArray());
                }

                break;
            }

            $reader->close();
        } else {
            $handle = fopen($path, 'rb');
            fread($handle, 3);

            while (($row = fgetcsv($handle, null, ';', '"', '')) !== false) {
                $rows[] = $row;
            }

            fclose($handle);
        }

        $header = array_shift($rows);

        return array_map(static fn (array $row): array => array_combine($header, array_map(static fn (int $index): string => $row[$index] ?? '', array_keys($header))), $rows);
    }
}
