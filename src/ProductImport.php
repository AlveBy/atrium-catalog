<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\DataImport;
use Atrium\Admin\Services\SlugService;
use Atrium\Admin\Transfer\ImportOption;
use Atrium\Admin\Transfer\Target;
use Atrium\Admin\Transfer\Trees;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;

/**
 * What the catalog adds to the import every module has: the headers price
 * lists use (article, SKU, price, category) and the sections a file names
 * that are not there yet, which the import creates when told to. Everything
 * else — fields, languages, images, prices — is read from the product's form.
 */
final class ProductImport
{
    /** Headers price lists use besides the labels of the form, normalized. */
    private const SYNONYMS = [
        'name' => ['product', 'productname', 'товар', 'названиетовара'],
        'article' => ['sku', 'vendorcode', 'partnumber', 'mpn', 'артикул'],
        'price' => ['cost', 'цена', 'стоимость'],
        'external_id' => ['xmlid', 'externalcode', 'guid', 'внешнийкод', 'внешнийid', 'код1с'],
        'section_id' => ['section', 'category', 'categories', 'group', 'раздел', 'категория', 'группа'],
        'photos' => ['images', 'gallery', 'галерея', 'фотографии'],
    ];

    public static function register(): void
    {
        Hook::filter('atrium.import.options', static fn (array $options, string $model): array => $model === Product::class
            ? [...$options, new ImportOption('create_sections', __('atrium-catalog::import.create_sections'), ImportOption::BOOLEAN, false)]
            : $options);

        Hook::filter('atrium.import.targets', static fn (array $targets, string $model): array => $model === Product::class
            ? self::targets($targets)
            : $targets);
    }

    /**
     * @param  array<string,Target>  $targets
     * @return array<string,Target>
     */
    private static function targets(array $targets): array
    {
        foreach (self::SYNONYMS as $key => $synonyms) {
            if (isset($targets[$key])) {
                $targets[$key] = $targets[$key]->withSynonyms($synonyms);
            }
        }

        if (isset($targets['section_id'])) {
            $targets['section_id'] = self::sections($targets['section_id']);
        }

        return $targets;
    }

    /**
     * The section by its name or path, as the form's tree reads it — and made
     * when it is not there and the import is told to.
     */
    private static function sections(Target $target): Target
    {
        $trees = null;

        return new Target(
            $target->key,
            $target->label,
            $target->field,
            synonyms: $target->synonyms,
            resolve: static function (string $cell, DataImport $import, bool $check) use ($target, &$trees): array {
                $trees ??= new Trees(
                    $target->field->toArray()['options'],
                    $target->label,
                    $import,
                    $check,
                    $import->option('create_sections') === true ? self::create(...) : null,
                );

                return ['section_id' => $trees->find($cell)];
            },
        );
    }

    private static function create(?int $parent, string $name): int
    {
        return CatalogSection::query()->create([
            'parent_id' => $parent,
            'name' => $name,
            'code' => app(SlugService::class)->generateUnique($name, CatalogSection::class),
            'active' => true,
        ])->id;
    }
}
