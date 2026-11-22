<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\CatalogServiceProvider;
use Atrium\Catalog\Import\ImportRunner;
use Atrium\Catalog\Import\ProcessImport;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

/**
 * A queue worker moves an import a step at a time, queueing the next step
 * itself; without one the scheduler does it every minute.
 */
final class ProcessImportTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/atrium-job-'.uniqid();
        config(['atrium-catalog.import.directory' => $this->directory]);
        File::ensureDirectoryExists($this->directory);
        Queue::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_a_queue_worker_moves_imports_unless_the_queue_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $this->assertFalse(ProcessImport::queued());

        config(['queue.default' => 'redis']);
        $this->assertTrue(ProcessImport::queued());
    }

    public function test_a_job_runs_once_for_up_to_five_minutes(): void
    {
        $job = new ProcessImport(7);

        $this->assertSame([7, 1, 300], [$job->import, $job->tries, $job->timeout]);
    }

    public function test_a_step_with_more_to_do_queues_the_next_one(): void
    {
        config(['atrium-catalog.import.seconds' => 0]);
        $import = $this->import("2,A-1,Parka\n3,A-2,Boots\n");

        (new ProcessImport($import->id))->handle(app(ImportRunner::class));

        $this->assertSame(1, $import->refresh()->processed);
        Queue::assertPushed(ProcessImport::class, static fn (ProcessImport $job): bool => $job->import === $import->id && $job->delay === 1);
    }

    public function test_the_last_step_queues_nothing(): void
    {
        $import = $this->import("2,A-1,Parka\n");

        (new ProcessImport($import->id))->handle(app(ImportRunner::class));

        $this->assertSame(CatalogImport::DONE, $import->refresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_an_import_that_was_deleted_is_left_alone(): void
    {
        (new ProcessImport(999))->handle(app(ImportRunner::class));

        Queue::assertNothingPushed();
    }

    public function test_the_scheduler_moves_the_imports_every_minute(): void
    {
        $events = array_values(array_filter(
            app(Schedule::class)->events(),
            static fn ($event): bool => str_contains((string) $event->command, 'atrium:catalog:import --pending'),
        ));

        $this->assertCount(1, $events);
        $this->assertSame('* * * * *', $events[0]->expression);
        $this->assertTrue($events[0]->withoutOverlapping);
    }

    public function test_the_config_and_the_translations_can_be_published(): void
    {
        $root = dirname(__DIR__, 3);

        $this->assertSame([$root.'/src/../config/atrium-catalog.php' => config_path('atrium-catalog.php')], CatalogServiceProvider::pathsToPublish(CatalogServiceProvider::class, 'atrium-catalog-config'));
        $this->assertSame([$root.'/src/../lang' => lang_path('vendor/atrium-catalog')], CatalogServiceProvider::pathsToPublish(CatalogServiceProvider::class, 'atrium-catalog-lang'));
        $this->assertSame(51200, config('atrium-catalog.import.max_size'));
    }

    private function import(string $rows): CatalogImport
    {
        $import = CatalogImport::query()->create([
            'status' => CatalogImport::NEW, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => ['Article', 'Name'], 'total' => 2,
            'mapping' => ['article', 'name'],
        ]);
        File::put($import->path(), $rows);
        $import->begin(CatalogImport::IMPORTING);

        return $import;
    }
}
