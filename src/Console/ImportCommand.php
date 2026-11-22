<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Console;

use Atrium\Catalog\Import\Cells;
use Atrium\Catalog\Import\ImportFailed;
use Atrium\Catalog\Import\ImportRunner;
use Atrium\Catalog\Import\Settings;
use Atrium\Catalog\Import\SourceFile;
use Atrium\Catalog\Import\Targets;
use Atrium\Catalog\Models\CatalogImport;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The import from the console and the scheduler: a file or a URL, mapped the
 * way an earlier import from the panel was (--like) or by its headers, the
 * columns that need it named with --map. It runs to the end and prints the
 * report; it fails when a row did not import, so a scheduled price list that
 * went wrong is noticed.
 *
 * With --pending it moves the imports started in the panel instead: the
 * module schedules that every minute, for sites without a queue worker.
 */
final class ImportCommand extends Command
{
    protected $signature = 'atrium:catalog:import
        {source? : A CSV or Excel (xlsx) file, or its URL}
        {--like= : Map the columns as the import with this number did, and take its options}
        {--map=* : A column and the field it fills, "Column=field"; "Column=" leaves it out}
        {--key= : Find products by article, code or external_id}
        {--mode= : both, create (new products only) or update (existing only)}
        {--create-sections : Create the sections the file names that are not there}
        {--separator= : What separates the sections of a path, "/" by default}
        {--check : Only check the file and report; write nothing}
        {--pending : Move the imports started in the panel a step forward}';

    protected $description = 'Import products into the catalog from a CSV or Excel file';

    public function handle(SourceFile $source, ImportRunner $runner, Targets $targets): int
    {
        if ($this->option('pending')) {
            foreach (CatalogImport::query()->whereIn('status', [CatalogImport::CHECKING, CatalogImport::IMPORTING])->get() as $import) {
                $runner->step($import, config('atrium-catalog.import.seconds'));
            }

            return self::SUCCESS;
        }

        $location = $this->argument('source');

        if ($location === null) {
            return $this->refuse(__('atrium-catalog::import.console.source'));
        }

        $like = $this->option('like') === null ? null : CatalogImport::query()->find($this->option('like'));

        if ($this->option('like') !== null && $like === null) {
            return $this->refuse(__('atrium-catalog::import.console.like', ['id' => $this->option('like')]));
        }

        $file = Str::uuid().'.csv';
        $target = CatalogImport::directory().'/'.$file;
        File::ensureDirectoryExists(CatalogImport::directory());

        try {
            $read = $this->read($source, $location, $target);
            $settings = Settings::validate([
                ...CatalogImport::DEFAULTS,
                ...($like->options ?? []),
                ...array_filter(['key' => $this->option('key'), 'mode' => $this->option('mode'), 'separator' => $this->option('separator')], static fn (?string $value): bool => $value !== null),
                ...($this->option('create-sections') ? ['create_sections' => true] : []),
                'mapping' => $this->mapping($read['headers'], $like, $targets),
            ], count($read['headers']), $targets);
        } catch (ImportFailed $failed) {
            File::delete($target);

            return $this->refuse($failed->getMessage());
        } catch (ValidationException $invalid) {
            File::delete($target);

            return $this->refuse(implode(PHP_EOL, Arr::flatten($invalid->errors())));
        }

        $import = CatalogImport::query()->create([
            ...$read,
            ...$settings,
            'status' => CatalogImport::NEW,
            'original_name' => mb_substr(basename((string) parse_url($location, PHP_URL_PATH)), 0, 255),
            'file' => $file,
        ]);

        $import->begin($this->option('check') ? CatalogImport::CHECKING : CatalogImport::IMPORTING);

        while ($runner->step($import, config('atrium-catalog.import.seconds'))) {
            // Each step saves its place; the next one goes on from there.
        }

        $import->refresh();
        $import->deleteFile();

        return $this->report($import);
    }

    /**
     * @return array{format:string,encoding:string,delimiter:string,headers:array<int,string>,total:int}
     */
    private function read(SourceFile $source, string $location, string $target): array
    {
        if (preg_match('#^https?://#i', $location) !== 1) {
            if (! is_file($location)) {
                throw ImportFailed::because('missing', ['file' => $location]);
            }

            return $source->normalize($location, $target);
        }

        $download = tempnam(sys_get_temp_dir(), 'atrium-import');

        try {
            $response = Http::timeout(120)->sink($download)->get($location);

            if (! $response->successful()) {
                throw ImportFailed::because('download', ['url' => $location]);
            }

            return $source->normalize($download, $target);
        } catch (ConnectionException) {
            throw ImportFailed::because('download', ['url' => $location]);
        } finally {
            File::delete($download);
        }
    }

    /**
     * The earlier import's mapping, column by column of the same name, or the
     * guess from the headers; then what --map says.
     *
     * @param  array<int,string>  $headers
     * @return array<int,string|null>
     */
    private function mapping(array $headers, ?CatalogImport $like, Targets $targets): array
    {
        $names = array_map(Cells::normalize(...), $headers);
        $mapping = $targets->guess($headers);

        if ($like !== null) {
            $known = array_combine(array_map(Cells::normalize(...), $like->headers), $like->mapping);
            $mapping = array_map(static fn (string $name): ?string => $known[$name] ?? null, $names);
        }

        foreach ($this->option('map') as $pair) {
            $equals = strrpos($pair, '=');
            $column = $equals === false ? false : array_search(Cells::normalize(substr($pair, 0, $equals)), $names, true);

            if ($column === false) {
                throw ImportFailed::because('column', ['column' => $pair]);
            }

            $field = substr($pair, $equals + 1);
            $mapping[$column] = $field === '' ? null : $field;
        }

        return $mapping;
    }

    private function report(CatalogImport $import): int
    {
        $this->line(__('atrium-catalog::import.console.report', $import->only(['processed', 'created', 'updated', 'skipped', 'failed', 'sections'])));

        foreach ($import->errors ?? [] as $error) {
            $this->line(__('atrium-catalog::import.console.row', ['row' => $error['row'], 'messages' => implode(' ', $error['messages'])]));
        }

        if ($import->status === CatalogImport::FAILED) {
            return $this->refuse($import->message);
        }

        return $import->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function refuse(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}
