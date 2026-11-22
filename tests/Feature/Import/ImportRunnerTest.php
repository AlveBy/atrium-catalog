<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\Import\ImportRunner;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * A check or an import moves a while at a time and saves its place; only one
 * step works on it at once, and a crash stops it with a reason.
 */
final class ImportRunnerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/atrium-runner-'.uniqid();
        config(['atrium-catalog.import.directory' => $this->directory, 'atrium-catalog.import.chunk' => 2]);
        File::ensureDirectoryExists($this->directory);
        $this->freezeTime();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_an_import_runs_to_the_end_chunk_after_chunk_and_deletes_its_file(): void
    {
        $import = $this->import(CatalogImport::IMPORTING, 5);

        $this->assertFalse($this->runner()->step($import, 20));

        $import->refresh();
        $this->assertSame(CatalogImport::DONE, $import->status);
        $this->assertSame([5, 5], [$import->processed, $import->created]);
        $this->assertFileDoesNotExist($this->directory.'/a.csv');
        $this->assertNotNull($import->finished_at);
        $this->assertSame(5, Product::query()->count());
    }

    public function test_a_check_ends_checked_and_keeps_the_file_for_the_import(): void
    {
        $import = $this->import(CatalogImport::CHECKING, 3);

        $this->assertFalse($this->runner()->step($import, 20));

        $import->refresh();
        $this->assertSame(CatalogImport::CHECKED, $import->status);
        $this->assertSame([3, 3], [$import->processed, $import->created]);
        $this->assertFileExists($this->directory.'/a.csv');
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_step_stops_at_its_deadline_and_saves_its_place(): void
    {
        $import = $this->import(CatalogImport::IMPORTING, 3);

        $this->assertTrue($this->runner()->step($import, 0));

        $saved = CatalogImport::query()->findOrFail($import->id);
        $this->assertSame(CatalogImport::IMPORTING, $saved->status);
        $this->assertSame(1, $saved->processed);
        $this->assertSame(strlen("2,P-2,\"Product 2\"\n"), $saved->position);
        $this->assertNull($saved->finished_at);

        $this->assertTrue($this->runner()->step($saved, 0));
        $this->assertFalse($this->runner()->step($saved, 0));
        $this->assertSame(CatalogImport::DONE, $saved->refresh()->status);
        $this->assertSame(['P-2', 'P-3', 'P-4'], Product::query()->orderBy('id')->pluck('article')->all());
    }

    public function test_a_step_goes_on_past_a_chunk_while_there_is_time(): void
    {
        $import = $this->import(CatalogImport::IMPORTING, 3);
        // The first row takes the whole time: the rest of the chunk waits.
        Product::created(fn () => $this->travel(30)->seconds());

        $this->assertTrue($this->runner()->step($import, 20));

        $this->assertSame(1, $import->refresh()->processed);
    }

    public function test_a_busy_import_is_left_alone(): void
    {
        $import = $this->import(CatalogImport::IMPORTING, 2);
        $lock = Cache::lock('atrium-catalog-import:'.$import->id, 10);
        $lock->get();

        $this->assertTrue($this->runner()->step($import, 20));
        $this->assertSame(0, $import->refresh()->processed);

        $lock->release();
        $this->assertFalse($this->runner()->step($import, 20));
        $this->assertTrue(Cache::lock('atrium-catalog-import:'.$import->id, 10)->get());
    }

    public function test_the_lock_outlives_the_step_by_a_minute(): void
    {
        $import = $this->import(CatalogImport::CHECKED, 2);
        $lock = Cache::lock('another', 80);
        Cache::partialMock()->shouldReceive('lock')->once()->with('atrium-catalog-import:'.$import->id, 80)->andReturn($lock);

        $this->assertFalse($this->runner()->step($import, 20));
    }

    public function test_an_import_that_is_not_running_is_not_moved(): void
    {
        $import = $this->import(CatalogImport::CHECKED, 2);

        $this->assertFalse($this->runner()->step($import, 20));
        $this->assertSame(0, $import->refresh()->processed);
    }

    public function test_the_import_is_read_again_before_the_step(): void
    {
        $import = $this->import(CatalogImport::IMPORTING, 2);
        CatalogImport::query()->whereKey($import->id)->update(['status' => CatalogImport::CANCELLED]);

        $this->assertFalse($this->runner()->step($import, 20));
        $this->assertSame(0, $import->refresh()->processed);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_a_crash_stops_the_import_with_a_reason_and_is_logged(): void
    {
        Exceptions::fake();
        $import = $this->import(CatalogImport::IMPORTING, 3);
        Product::creating(static fn (Product $product) => $product->article === 'P-3' ? throw new RuntimeException('Disk full') : null);

        $this->assertFalse($this->runner()->step($import, 20));

        $import->refresh();
        $this->assertSame(CatalogImport::FAILED, $import->status);
        $this->assertSame('The import stopped because of an error on the server. It has been logged; what had been imported stays.', $import->message);
        $this->assertSame(1, $import->processed);
        $this->assertNotNull($import->finished_at);
        $this->assertFileExists($this->directory.'/a.csv');
        Exceptions::assertReported(static fn (RuntimeException $crash): bool => $crash->getMessage() === 'Disk full');
    }

    private function runner(): ImportRunner
    {
        return app(ImportRunner::class);
    }

    private function import(string $status, int $rows): CatalogImport
    {
        $lines = '';

        for ($row = 2; $row < $rows + 2; $row++) {
            $lines .= "{$row},P-{$row},\"Product {$row}\"\n";
        }

        File::put($this->directory.'/a.csv', $lines);

        $import = CatalogImport::query()->create([
            'status' => CatalogImport::NEW, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => ['Article', 'Name'], 'total' => $rows,
            'mapping' => ['article', 'name'], 'options' => CatalogImport::DEFAULTS,
        ]);
        $import->begin($status);

        return $import;
    }
}
