<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Brands\BrandsServiceProvider;
use Atrium\Catalog\CatalogServiceProvider;
use Atrium\News\NewsServiceProvider;
use Atrium\Stores\StoresServiceProvider;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Every admin language of the demo shop's modules has every line English
 * has, and no other.
 */
final class TranslationsTest extends TestCase
{
    /**
     * @return array<string,array{class-string,string}>
     */
    public static function modules(): array
    {
        return [
            'catalog' => [CatalogServiceProvider::class, 'catalog'],
            'brands' => [BrandsServiceProvider::class, 'brands'],
            'stores' => [StoresServiceProvider::class, 'stores'],
            'news' => [NewsServiceProvider::class, 'news'],
        ];
    }

    /**
     * @param  class-string  $provider
     */
    #[DataProvider('modules')]
    public function test_every_language_has_the_english_keys(string $provider, string $file): void
    {
        $dir = dirname((string) (new ReflectionClass($provider))->getFileName(), 2).'/lang';
        $english = array_keys(Arr::dot(require $dir.'/en/'.$file.'.php'));
        $languages = array_diff(scandir($dir) ?: [], ['.', '..']);

        $this->assertCount(15, $languages);

        foreach ($languages as $language) {
            $lines = Arr::dot(require $dir.'/'.$language.'/'.$file.'.php');

            $this->assertSame($english, array_keys($lines), $language);
            $this->assertNotContains('', $lines, $language);
        }
    }
}
