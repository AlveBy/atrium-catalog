<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Admin\Fields\BelongsToManyField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\Field;
use Atrium\Admin\Fields\FileField;
use Atrium\Admin\Fields\LinkField;
use Atrium\Admin\Fields\MapField;
use Atrium\Admin\Fields\TreeField;
use Atrium\Admin\Fields\TreeSelectField;
use Atrium\Admin\Support\ContentLanguages;

/**
 * What a column of the file can fill: every field of the product form that
 * takes a value from text — the SEO fields and custom fields included, a
 * field kept per content language once per language — and the section, found
 * by its name or path instead of its id.
 *
 * The mapping is guessed from the headers: a header that reads like a field's
 * label in any of the panel's languages, its name or a usual synonym ("SKU",
 * "Цена", "Категория") maps to that field.
 */
final class Targets
{
    public const SECTION = 'section';

    /** Headers price lists use besides the labels of the form. */
    private const SYNONYMS = [
        'name' => ['title', 'product', 'productname', 'наименование', 'товар', 'названиетовара'],
        'code' => ['slug', 'url', 'чпу', 'символьныйкод'],
        'article' => ['sku', 'vendorcode', 'partnumber', 'mpn', 'артикул'],
        'price' => ['cost', 'цена', 'стоимость'],
        'external_id' => ['id', 'xmlid', 'externalcode', 'guid', 'внешнийкод', 'внешнийid', 'код1с'],
        self::SECTION => ['category', 'categories', 'group', 'раздел', 'категория', 'группа'],
        'detail_text' => ['description', 'описание'],
        'preview_text' => ['shortdescription', 'summary', 'анонс'],
        'preview_picture' => ['image', 'picture', 'photo', 'картинка', 'изображение', 'фото'],
        'photos' => ['images', 'gallery', 'галерея', 'фотографии'],
        'active' => ['enabled', 'published', 'активность'],
        'sort' => ['order', 'position', 'сортировка'],
    ];

    /** @var array<string,array{label:string,field:Field|null,locale:string|null,base?:string}>|null */
    private ?array $all = null;

    public function __construct(private readonly ContentLanguages $languages) {}

    /**
     * @return array<string,array{label:string,field:Field|null,locale:string|null,base?:string}>
     */
    public function all(): array
    {
        return $this->all ??= $this->build();
    }

    /**
     * @return array{label:string,field:Field|null,locale:string|null,base?:string}|null
     */
    public function get(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * For the mapping screen: key and label, in the form's order.
     *
     * @return array<int,array{key:string,label:string}>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->all() as $key => $target) {
            $options[] = ['key' => $key, 'label' => $target['label']];
        }

        return $options;
    }

    /**
     * A target for each header, or null. A field takes one column only: the
     * first header that names it.
     *
     * @param  array<int,string>  $headers
     * @return array<int,string|null>
     */
    public function guess(array $headers): array
    {
        $aliases = $this->aliases();
        $used = [];
        $mapping = [];

        foreach ($headers as $index => $header) {
            $key = $aliases[Cells::normalize($header)] ?? null;
            $mapping[$index] = null;

            if ($key !== null && ! isset($used[$key])) {
                $mapping[$index] = $key;
                $used[$key] = $index;
            }
        }

        return $mapping;
    }

    /**
     * @return array<string,array{label:string,field:Field|null,locale:string|null,base?:string}>
     */
    private function build(): array
    {
        $writer = new ProductWriter;
        $translatable = $writer->translatable();
        $multilingual = $this->languages->isMultilingual();
        $targets = [];

        foreach ($writer->fields() as $field) {
            $name = $field->getName();

            if ($name === 'section_id') {
                $label = $field->getLabel();
                $targets[self::SECTION] = ['label' => $label, 'field' => null, 'locale' => null];
            }

            if (self::skipped($field)) {
                continue;
            }

            if ($multilingual && in_array($name, $translatable, true)) {
                foreach ($this->languages->all() as $language) {
                    $targets["{$name}.{$language->code}"] = [
                        'label' => "{$field->getLabel()} ({$language->name})",
                        'base' => $field->getLabel(),
                        'field' => $field,
                        'locale' => $language->code,
                    ];
                }

                continue;
            }

            $targets[$name] = ['label' => $field->getLabel(), 'field' => $field, 'locale' => null];
        }

        return $targets;
    }

    /**
     * Fields a cell of text cannot fill: read-only ones, links to many
     * records, trees, maps, documents.
     */
    private static function skipped(Field $field): bool
    {
        foreach ([DisplayField::class, BelongsToManyField::class, TreeSelectField::class, TreeField::class, FileField::class, LinkField::class, MapField::class] as $class) {
            if ($field instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalized header => target key: the labels in every language of the
     * panel, the keys themselves, the synonyms. What comes first wins.
     *
     * @return array<string,string>
     */
    private function aliases(): array
    {
        $aliases = [];
        $default = $this->languages->default();
        $current = app()->getLocale();

        foreach (array_keys(config('atrium.locales')) as $locale) {
            app()->setLocale($locale);

            foreach ($this->build() as $key => $target) {
                $aliases[Cells::normalize($target['label'])] ??= $key;

                // "Name" alone is the default language's name.
                if ($target['locale'] === $default) {
                    $aliases[Cells::normalize($target['base'] ?? '')] ??= $key;
                }
            }
        }

        app()->setLocale($current);

        foreach (array_keys($this->all()) as $key) {
            $aliases[Cells::normalize($key)] ??= $key;
        }

        // A module may have taken a field out of the form (atrium.module.fields).
        foreach (array_intersect_key(self::SYNONYMS, $this->all()) as $key => $synonyms) {
            foreach ($synonyms as $synonym) {
                $aliases[$synonym] ??= $key;
            }
        }

        return $aliases;
    }
}
