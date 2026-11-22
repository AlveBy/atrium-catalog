<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Admin\Services\SlugService;
use Atrium\Catalog\Models\CatalogImport;
use Atrium\Catalog\Models\CatalogSection;

/**
 * Finds the section a row names: by its name ("Куртки") anywhere in the tree,
 * or by its path from the top ("Одежда / Куртки"), case aside. A section that
 * is not there is created, the missing part of the path with it, when the
 * import is told to, with a code made from its name as the form makes it; a
 * check only counts what it would create.
 */
final class Sections
{
    /** @var array<int,array{parent:int|null,name:string}> id => section */
    private array $sections = [];

    public function __construct(private readonly CatalogImport $import, private readonly bool $check)
    {
        foreach (CatalogSection::query()->orderBy('id')->get(['id', 'parent_id', 'name']) as $section) {
            $this->sections[$section->id] = ['parent' => $section->parent_id, 'name' => mb_strtolower($section->name)];
        }
    }

    /**
     * The section's id; null in a check for a section it would create.
     */
    public function find(string $value): ?int
    {
        $parts = array_values(array_filter(
            array_map(trim(...), explode($this->import->option('separator'), $value)),
            static fn (string $part): bool => $part !== '',
        ));

        if ($parts === []) {
            throw RowProblem::because('section_missing', ['section' => $value]);
        }

        if (count($parts) === 1) {
            $found = $this->named($parts[0]);

            if (count($found) > 1) {
                throw RowProblem::because('section_ambiguous', ['section' => $value]);
            }

            if ($found !== []) {
                return $found[0];
            }
        }

        $parent = null;

        foreach ($parts as $depth => $part) {
            $child = $this->child($parent, $part);

            if ($child === null) {
                return $this->create(array_slice($parts, 0, $depth), array_slice($parts, $depth), $parent, $value);
            }

            $parent = $child;
        }

        return $parent;
    }

    /**
     * @return array<int,int>
     */
    private function named(string $name): array
    {
        $name = mb_strtolower($name);

        return array_keys(array_filter($this->sections, static fn (array $section): bool => $section['name'] === $name));
    }

    private function child(?int $parent, string $name): ?int
    {
        $name = mb_strtolower($name);

        foreach ($this->sections as $id => $section) {
            if ($section['parent'] === $parent && $section['name'] === $name) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param  array<int,string>  $found  the part of the path that is there
     * @param  array<int,string>  $missing  the part to create
     */
    private function create(array $found, array $missing, ?int $parent, string $value): ?int
    {
        if ($this->import->option('create_sections') !== true) {
            throw RowProblem::because('section_missing', ['section' => $value]);
        }

        foreach ($missing as $name) {
            $found[] = $name;

            if ($this->check) {
                $this->import->sections += $this->import->remember('section', implode('/', $found)) ? 1 : 0;

                continue;
            }

            $section = CatalogSection::query()->create([
                'parent_id' => $parent,
                'name' => $name,
                'code' => app(SlugService::class)->generateUnique($name, CatalogSection::class),
                'active' => true,
            ]);

            $this->sections[$section->id] = ['parent' => $parent, 'name' => mb_strtolower($name)];
            $parent = $section->id;
            $this->import->sections++;
        }

        return $this->check ? null : $parent;
    }
}
