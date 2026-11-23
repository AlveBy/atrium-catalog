<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;

/**
 * Which texts of the module the AI helps with: the ones the editors write for
 * the site, and not the ones visitors write or the ones that are code.
 */
final class AiFieldsTest extends TestCase
{
    public function test_the_description_of_a_section_can_be_written_by_the_ai(): void
    {
        $this->assertSame(['task' => 'describe', 'from' => []], $this->field(CatalogSection::class, 'description')->toArray()['ai'] ?? null);
    }

    public function test_the_description_of_a_product_can_be_written_by_the_ai(): void
    {
        $this->assertSame(['task' => 'describe', 'from' => []], $this->field(Product::class, 'detail_text')->toArray()['ai'] ?? null);
    }

    public function test_the_preview_of_a_product_is_written_from_its_description(): void
    {
        $this->assertSame(['task' => 'preview', 'from' => ['detail_text']], $this->field(Product::class, 'preview_text')->toArray()['ai'] ?? null);
    }

    public function test_the_specs_and_the_package_of_a_product_are_left_alone(): void
    {
        foreach (['specs', 'package'] as $name) {
            $this->assertNull($this->field(Product::class, $name)->toArray()['ai'] ?? null, $name);
        }
    }

    /**
     * @param  class-string  $model
     */
    private function field(string $model, string $name): Field
    {
        $fields = method_exists($model, 'getAdminFieldsWithTabs') ? $model::getAdminFieldsWithTabs() : $model::getAdministrationFields();

        return collect($fields)->first(fn (Field $field): bool => $field->getName() === $name) ?? $this->fail("No field {$name} in {$model}.");
    }
}
