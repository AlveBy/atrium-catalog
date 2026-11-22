<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Unit;

use Atrium\Catalog\Models\Product;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The import asks a model for its keys from outside the model. An Eloquent
 * model forwards a call to a protected static method to itself, so the call
 * would work all the same: the method itself has to be public.
 */
final class ProductImportKeysVisibilityTest extends TestCase
{
    public function test_the_keys_of_the_import_are_a_public_static_method_of_the_product(): void
    {
        $method = new ReflectionMethod(Product::class, 'getImportKeys');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertSame(['article', 'code', 'external_id', 'id'], Product::getImportKeys());
        $this->assertSame(Product::class, $method->getDeclaringClass()->getName());
    }
}
