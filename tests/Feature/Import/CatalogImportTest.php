<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * An import keeps its file, its settings, its counters and the rows that
 * went wrong.
 */
final class CatalogImportTest extends TestCase
{
    public function test_files_live_in_the_configured_directory(): void
    {
        config(['atrium-catalog.import.directory' => '/var/imports/']);
        $import = $this->import(['file' => 'a.csv']);

        $this->assertSame('/var/imports', CatalogImport::directory());
        $this->assertSame('/var/imports/a.csv', $import->path());
    }

    public function test_an_option_falls_back_to_its_default(): void
    {
        $import = $this->import(['options' => ['key' => 'code', 'create_sections' => true]]);

        $this->assertSame('code', $import->option('key'));
        $this->assertTrue($import->option('create_sections'));
        $this->assertSame('both', $import->option('mode'));
        $this->assertSame('/', $this->import()->option('separator'));
    }

    /**
     * @return array<string,array{string,bool,bool}>
     */
    public static function statuses(): array
    {
        return [
            'new' => [CatalogImport::NEW, false, true],
            'checking' => [CatalogImport::CHECKING, true, false],
            'checked' => [CatalogImport::CHECKED, false, true],
            'importing' => [CatalogImport::IMPORTING, true, false],
            'done' => [CatalogImport::DONE, false, false],
            'failed' => [CatalogImport::FAILED, false, true],
            'cancelled' => [CatalogImport::CANCELLED, false, false],
        ];
    }

    #[DataProvider('statuses')]
    public function test_running_and_open(string $status, bool $running, bool $open): void
    {
        $import = $this->import(['status' => $status]);

        $this->assertSame($running, $import->isRunning());
        $this->assertSame($open, $import->isOpen());
    }

    public function test_a_value_is_remembered_once_per_kind_and_import_case_aside(): void
    {
        $import = $this->import();
        $other = $this->import();

        $this->assertFalse($import->seen('product', 'A-1'));
        $this->assertTrue($import->remember('product', 'A-1'));
        $this->assertFalse($import->remember('product', 'a-1'));
        $this->assertTrue($import->seen('product', 'a-1'));
        $this->assertFalse($import->seen('section', 'A-1'));
        $this->assertFalse($other->seen('product', 'A-1'));
        $this->assertTrue($other->remember('product', 'A-1'));
        $this->assertTrue($import->remember('brand', 'Ключ'));
        $this->assertTrue($import->seen('brand', 'КЛЮЧ'));
        $this->assertFalse($import->remember('brand', 'кЛЮЧ'));

        $import->forgetSeen();

        $this->assertFalse($import->seen('product', 'A-1'));
        $this->assertTrue($other->seen('product', 'A-1'));
    }

    public function test_begin_starts_from_the_first_row(): void
    {
        $this->travelTo('2026-11-22 10:00:00');
        $import = $this->import();
        $import->forceFill([
            'position' => 500, 'processed' => 9, 'created' => 1, 'updated' => 2, 'skipped' => 3, 'failed' => 4, 'sections' => 5,
            'errors' => [['row' => 2, 'skipped' => false, 'messages' => ['x']]], 'message' => 'Broken', 'finished_at' => now(),
        ])->save();
        $import->remember('product', 'A-1');

        $import->begin(CatalogImport::IMPORTING);
        $import->refresh();

        $this->assertSame(CatalogImport::IMPORTING, $import->status);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0], [$import->position, $import->processed, $import->created, $import->updated, $import->skipped, $import->failed, $import->sections]);
        $this->assertSame([], $import->errors);
        $this->assertNull($import->message);
        $this->assertSame('2026-11-22 10:00:00', $import->started_at?->toDateTimeString());
        $this->assertNull($import->finished_at);
        $this->assertFalse($import->seen('product', 'A-1'));
    }

    public function test_a_row_is_noted_and_the_list_stops_at_the_limit(): void
    {
        config(['atrium-catalog.import.errors' => 2]);
        $import = $this->import();

        $import->note(2, true, ['exists']);
        $import->note(3, false, ['bad']);
        $import->note(4, false, ['worse']);

        $this->assertSame(1, $import->skipped);
        $this->assertSame(2, $import->failed);
        $this->assertSame([
            ['row' => 2, 'skipped' => true, 'messages' => ['exists']],
            ['row' => 3, 'skipped' => false, 'messages' => ['bad']],
        ], $import->errors);
    }

    public function test_stop_saves_the_status_and_the_reason_and_forgets_what_it_saw(): void
    {
        $this->travelTo('2026-11-22 10:00:00');
        $import = $this->import(['status' => CatalogImport::IMPORTING]);
        $import->remember('product', 'A-1');

        $import->stop(CatalogImport::FAILED, 'Broken');
        $import->refresh();

        $this->assertSame([CatalogImport::FAILED, 'Broken'], [$import->status, $import->message]);
        $this->assertSame('2026-11-22 10:00:00', $import->finished_at?->toDateTimeString());
        $this->assertSame(0, DB::table('catalog_import_keys')->count());

        $import->stop(CatalogImport::CHECKED);
        $this->assertNull($import->refresh()->message);
    }

    public function test_the_file_is_deleted_once(): void
    {
        config(['atrium-catalog.import.directory' => sys_get_temp_dir()]);
        $import = $this->import(['file' => 'atrium-delete-'.bin2hex(random_bytes(4)).'.csv']);
        file_put_contents($import->path(), 'x');

        $import->deleteFile();
        $this->assertFileDoesNotExist($import->path());

        $import->deleteFile();
        $this->assertFileDoesNotExist($import->path());
    }

    public function test_it_belongs_to_the_administrator_who_uploaded_it(): void
    {
        $admin = $this->admin();

        $this->assertSame($admin->id, $this->import(['admin_user_id' => $admin->id])->user?->id);
        $this->assertNull($this->import()->user);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function import(array $attributes = []): CatalogImport
    {
        return CatalogImport::query()->create([
            'status' => CatalogImport::NEW,
            'original_name' => 'price.csv',
            'file' => 'price.csv',
            'format' => 'csv',
            'encoding' => 'UTF-8',
            'delimiter' => ';',
            'headers' => ['Name'],
            'total' => 1,
            ...$attributes,
        ]);
    }
}
