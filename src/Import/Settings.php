<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Catalog\Models\CatalogImport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The mapping and the options of an import, checked the same way whether the
 * panel or the console sets them: a target for a column at most, each target
 * for one column only, and the column the products are found by among them.
 */
final class Settings
{
    /**
     * @param  array<string,mixed>  $input  mapping (a target or null per column), key, mode, create_sections, separator
     * @return array{mapping:array<int,string|null>,options:array{key:string,mode:string,create_sections:bool,separator:string}}
     *
     * @throws ValidationException
     */
    public static function validate(array $input, int $columns, Targets $targets): array
    {
        $data = Validator::make($input, [
            'mapping' => ['required', 'list', 'size:'.$columns],
            'mapping.*' => ['nullable', 'string', Rule::in(array_keys($targets->all()))],
            'key' => ['required', Rule::in(CatalogImport::KEYS)],
            'mode' => ['required', Rule::in(CatalogImport::MODES)],
            'create_sections' => ['boolean'],
            'separator' => ['required', 'string', 'max:5'],
        ], [], self::attributes())->validate();

        $mapped = array_filter($data['mapping'], static fn (?string $target): bool => $target !== null);
        $twice = array_keys(array_filter(array_count_values($mapped), static fn (int $count): bool => $count > 1));

        if ($twice !== []) {
            throw ValidationException::withMessages(['mapping' => __('atrium-catalog::import.validation.twice', [
                'fields' => implode(', ', array_map(static fn (string $key): string => $targets->get($key)['label'], $twice)),
            ])]);
        }

        if (! in_array($data['key'], $mapped, true)) {
            throw ValidationException::withMessages(['key' => __('atrium-catalog::import.validation.key', [
                'field' => $targets->get($data['key'])['label'] ?? $data['key'],
            ])]);
        }

        return [
            'mapping' => $data['mapping'],
            'options' => [
                'key' => $data['key'],
                'mode' => $data['mode'],
                'create_sections' => (bool) ($data['create_sections'] ?? false),
                'separator' => $data['separator'],
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    private static function attributes(): array
    {
        $attributes = [];

        foreach (['mapping', 'key', 'mode', 'create_sections', 'separator'] as $name) {
            $attributes[$name] = __('atrium-catalog::import.settings.'.$name);
        }

        $attributes['mapping.*'] = $attributes['mapping'];

        return $attributes;
    }
}
