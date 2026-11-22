<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Catalog\Import\RowProblem;
use Atrium\Catalog\Import\Sections;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A row names its section by name or by path; one that is not there is
 * created when the import is told to.
 */
final class SectionsTest extends TestCase
{
    private CatalogSection $clothes;

    private CatalogSection $jackets;

    private CatalogSection $shoes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clothes = CatalogSection::query()->create(['name' => 'Одежда', 'code' => 'clothes', 'active' => true]);
        $this->jackets = CatalogSection::query()->create(['name' => 'Куртки', 'code' => 'jackets', 'parent_id' => $this->clothes->id, 'active' => true]);
        $this->shoes = CatalogSection::query()->create(['name' => 'Обувь', 'code' => 'shoes', 'active' => true]);
        // A second "Детское" under each: the name alone does not say which.
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-clothes', 'parent_id' => $this->clothes->id, 'active' => true]);
        CatalogSection::query()->create(['name' => 'Детское', 'code' => 'kids-shoes', 'parent_id' => $this->shoes->id, 'active' => true]);
    }

    public function test_a_name_anywhere_in_the_tree_case_aside(): void
    {
        $sections = new Sections($this->import(), false);

        $this->assertSame($this->jackets->id, $sections->find('куртки'));
        $this->assertSame($this->shoes->id, $sections->find(' Обувь '));
    }

    public function test_a_path_from_the_top(): void
    {
        $sections = new Sections($this->import(), false);
        $kids = CatalogSection::query()->where('code', 'kids-shoes')->value('id');

        $this->assertSame($this->jackets->id, $sections->find('Одежда / Куртки'));
        $this->assertSame($this->jackets->id, $sections->find('одежда/куртки/'));
        $this->assertSame($kids, $sections->find('Обувь / Детское'));
        $this->assertSame($this->clothes->id, $sections->find('/ Одежда'));
    }

    public function test_a_path_with_another_separator(): void
    {
        $sections = new Sections($this->import(['separator' => '>']), false);

        $this->assertSame($this->jackets->id, $sections->find('Одежда > Куртки'));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function problems(): array
    {
        return [
            'two sections of that name' => ['Детское', 'More than one section is called "Детское": write its path, "Parent / Детское".'],
            'not there' => ['Шапки', 'There is no section "Шапки".'],
            'a path that is not there' => ['Одежда / Шапки', 'There is no section "Одежда / Шапки".'],
            'a name under the wrong parent' => ['Обувь / Куртки', 'There is no section "Обувь / Куртки".'],
            'separators only' => [' / ', 'There is no section " / ".'],
        ];
    }

    #[DataProvider('problems')]
    public function test_what_cannot_be_found(string $value, string $message): void
    {
        $this->expectException(RowProblem::class);
        $this->expectExceptionMessage($message);

        (new Sections($this->import(), false))->find($value);
    }

    public function test_separators_only_are_not_created_either(): void
    {
        $this->expectException(RowProblem::class);

        (new Sections($this->import(['create_sections' => true]), false))->find(' / ');
    }

    public function test_missing_sections_are_created_with_the_path_to_them(): void
    {
        $import = $this->import(['create_sections' => true]);
        $sections = new Sections($import, false);

        $hats = $sections->find('Одежда / Шапки / Зимние');
        $again = $sections->find('Одежда / Шапки / Зимние');
        $alone = $sections->find('Носки');
        // A code already taken gets a number.
        $other = $sections->find('Обувь / Зимние');

        $created = CatalogSection::query()->where('code', 'shapki')->sole();
        $winter = CatalogSection::query()->findOrFail($hats);

        $this->assertSame([$this->clothes->id, 'Шапки', true], [$created->parent_id, $created->name, $created->active]);
        $this->assertSame([$created->id, 'Зимние', 'zimnie'], [$winter->parent_id, $winter->name, $winter->code]);
        $this->assertSame($hats, $again);
        $this->assertNull(CatalogSection::query()->findOrFail($alone)->parent_id);
        $this->assertSame(['zimnie-1', $this->shoes->id], [CatalogSection::query()->findOrFail($other)->code, CatalogSection::query()->findOrFail($other)->parent_id]);
        $this->assertSame(4, $import->sections);
        $this->assertSame($winter->id, $sections->find('Одежда / Шапки / зимние'));
    }

    public function test_a_check_counts_the_sections_it_would_create_once_and_creates_none(): void
    {
        $import = $this->import(['create_sections' => true]);
        $sections = new Sections($import, true);

        $this->assertNull($sections->find('Одежда / Шапки / Зимние'));
        $this->assertNull($sections->find('одежда / шапки / зимние'));
        $this->assertNull($sections->find('Одежда / Шапки'));
        $this->assertNull($sections->find('Носки'));
        $this->assertSame($this->jackets->id, $sections->find('Куртки'));

        $this->assertSame(3, $import->sections);
        $this->assertSame(5, CatalogSection::query()->count());
    }

    public function test_a_check_tells_the_same_new_name_under_two_parents_apart(): void
    {
        $import = $this->import(['create_sections' => true]);
        $sections = new Sections($import, true);

        $sections->find('Одежда / Зимние');
        $sections->find('Обувь / Зимние');

        $this->assertSame(2, $import->sections);
    }

    /**
     * @param  array<string,mixed>  $options
     */
    private function import(array $options = []): CatalogImport
    {
        return CatalogImport::query()->create([
            'status' => CatalogImport::IMPORTING, 'original_name' => 'a.csv', 'file' => 'a.csv', 'format' => 'csv',
            'encoding' => 'UTF-8', 'delimiter' => ';', 'headers' => ['Section'], 'total' => 1,
            'options' => [...CatalogImport::DEFAULTS, ...$options],
        ]);
    }
}
