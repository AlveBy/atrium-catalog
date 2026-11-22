<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * `atrium:catalog:import`: a file or a URL from the console or a schedule,
 * run to the end with a report; --pending moves what the panel started.
 */
final class ImportCommandTest extends TestCase
{
    private string $directory;

    private string $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/atrium-command-'.uniqid();
        $this->files = sys_get_temp_dir().'/atrium-command-files-'.uniqid();
        config(['atrium-catalog.import.directory' => $this->directory]);
        File::ensureDirectoryExists($this->files);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        File::deleteDirectory($this->files);

        parent::tearDown();
    }

    public function test_a_file_is_imported_to_the_end_with_a_report(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование;Цена\nA-1;Parka;100\nA-2;Boots;90\n");

        $this->artisan('atrium:catalog:import', ['source' => $file])
            ->expectsOutput('Rows: 2. Created: 2, updated: 0, skipped: 0, errors: 0, sections created: 0.')
            ->assertExitCode(0);

        $import = CatalogImport::query()->sole();
        $this->assertSame([CatalogImport::DONE, 'price.csv', null], [$import->status, $import->original_name, $import->admin_user_id]);
        $this->assertSame(['article', 'name', 'price'], $import->mapping);
        $this->assertSame(['key' => 'article', 'mode' => 'both', 'create_sections' => false, 'separator' => '/'], $import->options);
        $this->assertSame(['A-1', 'A-2'], Product::query()->orderBy('id')->pluck('article')->all());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_check_writes_nothing_and_keeps_no_file(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование\nA-1;Parka\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--check' => true])
            ->expectsOutput('Rows: 1. Created: 1, updated: 0, skipped: 0, errors: 0, sections created: 0.')
            ->assertExitCode(0);

        $this->assertSame(CatalogImport::CHECKED, CatalogImport::query()->sole()->status);
        $this->assertSame(0, Product::query()->count());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_row_that_did_not_import_fails_the_command(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование\nA-1;\n;Boots\nA-3;Hat\n");

        $this->artisan('atrium:catalog:import', ['source' => $file])
            ->expectsOutput('Rows: 3. Created: 1, updated: 0, skipped: 0, errors: 2, sections created: 0.')
            ->expectsOutput('Row 2: The name field is required.')
            ->expectsOutput('Row 3: "Article number" is empty: the product cannot be found or created.')
            ->assertExitCode(1);
    }

    public function test_every_reason_of_a_row_is_on_its_line(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование;Цена;Сортировка\nA-1;;cheap;x\n");

        $this->artisan('atrium:catalog:import', ['source' => $file])
            ->expectsOutput('Row 2: The name field is required. The sort field must be a number. The price field must be a number.')
            ->assertExitCode(1);
    }

    public function test_options_from_the_command_line(): void
    {
        Product::query()->create(['code' => 'parka', 'name' => 'Parka', 'price' => 1]);
        $file = $this->file('price.csv', "Символьный код;Цена;Раздел\nparka;5;Одежда > Куртки\nnew;6;Одежда\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--key' => 'code', '--mode' => 'update', '--separator' => '>', '--create-sections' => true])
            ->expectsOutput('Rows: 2. Created: 0, updated: 1, skipped: 1, errors: 0, sections created: 2.')
            ->assertExitCode(0);

        $this->assertSame(['key' => 'code', 'mode' => 'update', 'create_sections' => true, 'separator' => '>'], CatalogImport::query()->sole()->options);
        $this->assertSame('5.00', Product::query()->sole()->price);
        $this->assertSame('Куртки', CatalogSection::query()->find(Product::query()->sole()->section_id)?->name);
    }

    public function test_like_takes_the_mapping_and_the_options_of_an_earlier_import(): void
    {
        $earlier = CatalogImport::query()->create([
            'status' => CatalogImport::DONE, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv', 'encoding' => 'UTF-8',
            'delimiter' => ';', 'headers' => ['Код', 'Title', 'Price, $'], 'mapping' => ['code', 'name', null], 'total' => 1,
            'options' => ['key' => 'code', 'mode' => 'both', 'create_sections' => false, 'separator' => '/'],
        ]);
        $file = $this->file('price.csv', "price \$;код;New column\n7;hat;x\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--like' => $earlier->id, '--map' => ['price $=price', 'New column=name']])
            ->assertExitCode(0);

        $import = CatalogImport::query()->latest('id')->first();
        $this->assertSame(['price', 'code', 'name'], $import?->mapping);
        $this->assertSame('code', $import?->options['key']);
        $this->assertSame(['hat', 'x', '7.00'], [Product::query()->sole()->code, Product::query()->sole()->name, Product::query()->sole()->price]);
    }

    public function test_like_options_give_way_to_the_command_line(): void
    {
        $earlier = CatalogImport::query()->create([
            'status' => CatalogImport::DONE, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv', 'encoding' => 'UTF-8',
            'delimiter' => ';', 'headers' => ['Article', 'Name'], 'mapping' => ['article', 'name'], 'total' => 1,
            'options' => ['key' => 'code', 'mode' => 'update', 'create_sections' => false, 'separator' => '/'],
        ]);
        $file = $this->file('price.csv', "Article;Name\nA-1;Parka\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--like' => $earlier->id, '--key' => 'article', '--mode' => 'both'])->assertExitCode(0);

        $this->assertSame(['key' => 'article', 'mode' => 'both', 'create_sections' => false, 'separator' => '/'], CatalogImport::query()->latest('id')->first()?->options);
    }

    public function test_map_names_a_column_with_an_equals_sign_and_leaves_one_out(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование;a=b\nA-1;Parka;Hat\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--map' => ['Наименование=', 'a=b=name']])->assertExitCode(0);

        $this->assertSame(['article', null, 'name'], CatalogImport::query()->sole()->mapping);
        $this->assertSame('Hat', Product::query()->sole()->name);
    }

    public function test_map_with_a_column_that_is_not_there_is_refused(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование\nA-1;Parka\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--map' => ['Цена=price']])
            ->expectsOutput('No such column for --map: "Цена=price". Write it as "Column=field".')
            ->assertExitCode(1);
        $this->artisan('atrium:catalog:import', ['source' => $file, '--map' => ['price']])
            ->expectsOutput('No such column for --map: "price". Write it as "Column=field".')
            ->assertExitCode(1);

        $this->assertSame(0, CatalogImport::query()->count());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_settings_that_do_not_fit_are_refused(): void
    {
        $file = $this->file('price.csv', "Артикул;Наименование\nA-1;Parka\n");

        $this->artisan('atrium:catalog:import', ['source' => $file, '--key' => 'name', '--mode' => 'all'])
            ->expectsOutput('The selected Find products by is invalid.'.PHP_EOL.'The selected What to do is invalid.')
            ->assertExitCode(1);

        $this->assertSame(0, CatalogImport::query()->count());
        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_file_that_cannot_be_read_is_refused(): void
    {
        $file = $this->file('empty.csv', "Артикул;Наименование\n");

        $this->artisan('atrium:catalog:import', ['source' => $file])
            ->expectsOutput('The file has no rows.')
            ->assertExitCode(1);

        $this->assertSame([], File::files($this->directory));
    }

    public function test_a_file_that_is_not_there(): void
    {
        $this->artisan('atrium:catalog:import', ['source' => $this->files.'/none.csv'])
            ->expectsOutput('There is no file '.$this->files.'/none.csv.')
            ->assertExitCode(1);
    }

    public function test_a_source_is_needed(): void
    {
        $this->artisan('atrium:catalog:import')
            ->expectsOutput('Name the file or its URL.')
            ->assertExitCode(1);
    }

    public function test_like_needs_an_import_that_is_there(): void
    {
        $this->artisan('atrium:catalog:import', ['source' => $this->file('a.csv', "A\n1\n"), '--like' => 999])
            ->expectsOutput('There is no import number 999.')
            ->assertExitCode(1);
    }

    public function test_a_file_is_downloaded_from_its_url_and_not_kept(): void
    {
        $before = glob(sys_get_temp_dir().'/atrium-import*') ?: [];
        Http::fake(['https://example.com/*' => Http::response("Артикул;Наименование\nA-1;Parka\n")]);

        $this->artisan('atrium:catalog:import', ['source' => 'https://example.com/feeds/price.csv?token=1'])->assertExitCode(0);

        $this->assertSame('price.csv', CatalogImport::query()->sole()->original_name);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame($before, glob(sys_get_temp_dir().'/atrium-import*') ?: []);
    }

    public function test_a_url_that_does_not_give_the_file(): void
    {
        $before = glob(sys_get_temp_dir().'/atrium-import*') ?: [];
        Http::fake([
            'https://example.com/missing.csv' => Http::response('Not found', 404),
            'https://down.example.com/*' => Http::failedConnection(),
        ]);

        $this->artisan('atrium:catalog:import', ['source' => 'https://example.com/missing.csv'])
            ->expectsOutput('The file could not be downloaded from https://example.com/missing.csv.')
            ->assertExitCode(1);
        $this->artisan('atrium:catalog:import', ['source' => 'https://down.example.com/price.csv'])
            ->expectsOutput('The file could not be downloaded from https://down.example.com/price.csv.')
            ->assertExitCode(1);

        $this->assertSame(0, CatalogImport::query()->count());
        $this->assertSame($before, glob(sys_get_temp_dir().'/atrium-import*') ?: []);
    }

    public function test_a_crash_fails_the_command_with_the_reason(): void
    {
        Exceptions::fake();
        Product::creating(static fn () => throw new RuntimeException('Disk full'));
        $file = $this->file('price.csv', "Артикул;Наименование\nA-1;Parka\n");

        $this->artisan('atrium:catalog:import', ['source' => $file])
            ->expectsOutput('The import stopped because of an error on the server. It has been logged; what had been imported stays.')
            ->assertExitCode(1);

        $this->assertSame(CatalogImport::FAILED, CatalogImport::query()->sole()->status);
        $this->assertSame([], File::files($this->directory));
    }

    public function test_pending_moves_what_the_panel_started_and_nothing_else(): void
    {
        config(['atrium-catalog.import.seconds' => 0]);
        File::ensureDirectoryExists($this->directory);
        $checking = $this->started(CatalogImport::CHECKING);
        $importing = $this->started(CatalogImport::IMPORTING);
        $checked = $this->started(CatalogImport::CHECKED);
        $new = $this->started(CatalogImport::NEW);

        $this->artisan('atrium:catalog:import', ['--pending' => true])->assertExitCode(0);

        $this->assertSame(1, $checking->refresh()->processed);
        $this->assertSame(1, $importing->refresh()->processed);
        $this->assertSame(0, $checked->refresh()->processed);
        $this->assertSame(0, $new->refresh()->processed);
        $this->assertSame(1, Product::query()->count());
    }

    private function started(string $status): CatalogImport
    {
        $import = CatalogImport::query()->create([
            'status' => CatalogImport::NEW, 'original_name' => 'a.csv', 'file' => uniqid().'.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => ['Article', 'Name'], 'total' => 2,
            'mapping' => ['article', 'name'],
        ]);
        File::put($import->path(), "2,A-{$import->id},Parka\n3,B-{$import->id},Boots\n");
        $import->begin($status);

        return $import;
    }

    private function file(string $name, string $content): string
    {
        File::put($this->files.'/'.$name, $content);

        return $this->files.'/'.$name;
    }
}
