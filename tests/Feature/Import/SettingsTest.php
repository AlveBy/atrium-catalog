<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Facades\Hook;
use Atrium\Catalog\Import\Settings;
use Atrium\Catalog\Import\Targets;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The mapping and the options are checked the same way for the panel and
 * the console.
 */
final class SettingsTest extends TestCase
{
    public function test_valid_settings_come_back_normalized(): void
    {
        $settings = Settings::validate([
            'mapping' => ['name', null, 'article'],
            'key' => 'article',
            'mode' => 'update',
            'create_sections' => '1',
            'separator' => '>',
            'extra' => 'ignored',
        ], 3, app(Targets::class));

        $this->assertSame([
            'mapping' => ['name', null, 'article'],
            'options' => ['key' => 'article', 'mode' => 'update', 'create_sections' => true, 'separator' => '>'],
        ], $settings);
    }

    public function test_sections_are_not_created_unless_asked(): void
    {
        $settings = Settings::validate(['mapping' => ['code'], 'key' => 'code', 'mode' => 'both', 'separator' => '/'], 1, app(Targets::class));

        $this->assertFalse($settings['options']['create_sections']);
    }

    /**
     * @return array<string,array{array<string,mixed>,string,string}>
     */
    public static function invalid(): array
    {
        $valid = ['mapping' => ['name', 'article'], 'key' => 'article', 'mode' => 'both', 'create_sections' => false, 'separator' => '/'];

        return [
            'no mapping' => [['mapping' => null] + $valid, 'mapping', 'The Columns field is required.'],
            'a column too few' => [['mapping' => ['name']] + $valid, 'mapping', 'The Columns field must contain 2 items.'],
            'not a list' => [['mapping' => [1 => 'name', 2 => 'article']] + $valid, 'mapping', 'The Columns field must be a list.'],
            'an unknown field' => [['mapping' => ['name', 'nonsense']] + $valid, 'mapping.1', 'The selected Columns is invalid.'],
            'a field twice' => [['mapping' => ['article', 'article']] + $valid, 'mapping', 'A field takes one column only; chosen more than once: Article number.'],
            'an unknown key' => [['key' => 'name'] + $valid, 'key', 'The selected Find products by is invalid.'],
            'the key not mapped' => [['key' => 'code'] + $valid, 'key', 'Products are found by "Code", but no column fills it.'],
            'an unknown mode' => [['mode' => 'replace'] + $valid, 'mode', 'The selected What to do is invalid.'],
            'not a yes or no' => [['create_sections' => 'maybe'] + $valid, 'create_sections', 'The Create the sections that are not there field must be true or false.'],
            'no separator' => [['separator' => ''] + $valid, 'separator', 'The Section path separator field is required.'],
            'a long separator' => [['separator' => '------'] + $valid, 'separator', 'The Section path separator field must not be greater than 5 characters.'],
        ];
    }

    /**
     * @param  array<string,mixed>  $input
     */
    #[DataProvider('invalid')]
    public function test_invalid_settings_say_what_is_wrong(array $input, string $key, string $message): void
    {
        try {
            Settings::validate($input, 2, app(Targets::class));
            $this->fail('The settings were accepted.');
        } catch (ValidationException $invalid) {
            $this->assertSame([$key => [$message]], $invalid->errors());
        }
    }

    public function test_the_key_is_named_even_when_the_form_has_lost_its_field(): void
    {
        Hook::filter('atrium.module.fields', static fn (array $fields): array => array_values(array_filter($fields, static fn ($field): bool => $field->getName() !== 'external_id')));

        try {
            Settings::validate(['mapping' => ['name'], 'key' => 'external_id', 'mode' => 'both', 'separator' => '/'], 1, app(Targets::class));
            $this->fail('The settings were accepted.');
        } catch (ValidationException $invalid) {
            $this->assertSame(['key' => ['Products are found by "external_id", but no column fills it.']], $invalid->errors());
        }
    }
}
