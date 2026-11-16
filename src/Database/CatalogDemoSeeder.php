<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Modules\Soft;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Stores\Models\Store;

/**
 * The demo shop's catalog: electronics in nested sections, every kind of
 * property filled in. With the Brands and Stores modules (their demo runs
 * first) the products get their brands and the stores that sell them.
 */
final class CatalogDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (Product::query()->exists()) {
            return false;
        }

        $this->copyFiles(dirname(__DIR__, 2).'/resources/demo');
        $this->products($this->sections());

        return true;
    }

    /**
     * @return array<string,CatalogSection>
     */
    private function sections(): array
    {
        $tree = [
            'computers' => ['Computers', 'laptop.svg', '<p>Laptops and tablets for work, study and everything in between.</p>', [
                'laptops' => ['Laptops', 'laptop-open.svg', '<p>From a light 14-inch for the road to a 16-inch workstation.</p>'],
                'tablets' => ['Tablets', 'tablet.svg', null],
            ]],
            'audio' => ['Audio', 'headphones.svg', '<p>Headphones and speakers.</p>', [
                'headphones' => ['Headphones', 'headphones.svg', null],
                'speakers' => ['Speakers', 'speaker.svg', null],
            ]],
            'wearables' => ['Wearables', 'watch.svg', '<p>Watches and trackers.</p>', []],
        ];

        $sections = [];
        $sort = 100;

        foreach ($tree as $code => [$name, $picture, $description, $children]) {
            $parent = $sections[$code] = CatalogSection::query()->create([
                'name' => $name, 'code' => $code, 'sort' => $sort += 100, 'picture' => $this->path($picture), 'description' => $description,
            ]);

            foreach ($children as $childCode => [$childName, $childPicture, $childDescription]) {
                $sections[$childCode] = CatalogSection::query()->create([
                    'parent_id' => $parent->id, 'name' => $childName, 'code' => $childCode, 'sort' => $sort += 10,
                    'picture' => $this->path($childPicture), 'description' => $childDescription,
                ]);
            }
        }

        return $sections;
    }

    /**
     * @param  array<string,CatalogSection>  $sections
     */
    private function products(array $sections): void
    {
        $products = [
            'aero-14' => [
                'section' => 'laptops', 'brand' => 'lumen', 'name' => 'Aero 14', 'sort' => 100,
                'picture' => 'laptop.svg', 'article' => 'LM-A14', 'price' => 1199, 'label' => 'hit', 'colors' => ['silver', 'blue'],
                'released_on' => '2026-03-12', 'in_stock' => 'Y',
                'preview' => '<p>A 14-inch laptop that weighs 1.2 kg and lasts a working day and a half.</p>',
                'detail' => '<p>The Aero 14 is built for people who work where they happen to be. The aluminium case is 14 mm thin, the screen is bright enough for a sunny café, and the battery lasts up to 18 hours.</p><p>Two USB-C ports charge it from any power bank.</p>',
                'features' => ['18 hours of battery', '1.2 kg', 'Matte 2.8K screen'],
                'specs' => '<table><tbody><tr><th>Screen</th><td>14", 2880 × 1800, 120 Hz</td></tr><tr><th>Memory</th><td>16 GB</td></tr><tr><th>Storage</th><td>512 GB SSD</td></tr><tr><th>Weight</th><td>1.2 kg</td></tr></tbody></table>',
                'package' => "Laptop\n65 W USB-C charger\nQuick start guide",
                'photos' => ['laptop.svg', 'laptop-open.svg', 'laptop-side.svg'], 'manual' => 'quick-start.pdf',
                'seo' => ['title' => 'Aero 14 laptop: 1.2 kg, 18 hours of battery', 'description' => 'The Aero 14 by Lumen: a 14-inch laptop for work on the road. Price, specifications and where to buy.'],
            ],
            'aero-16-pro' => [
                'section' => 'laptops', 'brand' => 'lumen', 'name' => 'Aero 16 Pro', 'sort' => 200,
                'picture' => 'laptop-pro.svg', 'article' => 'LM-A16P', 'price' => 1999, 'label' => 'new', 'colors' => ['black'],
                'released_on' => '2026-09-01', 'in_stock' => 'Y',
                'preview' => '<p>A 16-inch workstation for photo and video.</p>',
                'detail' => '<p>More screen, more memory and a graphics card that renders a 4K video while you answer e-mail.</p>',
                'features' => ['32 GB of memory', 'Calibrated screen'],
                'specs' => '<ul><li>16", 3456 × 2160</li><li>32 GB / 1 TB</li><li>2.1 kg</li></ul>',
                'photos' => ['laptop-pro.svg', 'laptop-open.svg'],
            ],
            'slate-11' => [
                'section' => 'tablets', 'brand' => 'lumen', 'name' => 'Slate 11', 'sort' => 300,
                'picture' => 'tablet.svg', 'article' => 'LM-S11', 'price' => 549, 'colors' => ['silver', 'pink'],
                'released_on' => '2025-11-20', 'in_stock' => 'Y',
                'preview' => '<p>An 11-inch tablet with a pen that is always charged.</p>',
            ],
            'quiet-one' => [
                'section' => 'headphones', 'brand' => 'vantor', 'name' => 'Quiet One', 'sort' => 400,
                'picture' => 'headphones.svg', 'article' => 'VT-Q1', 'price' => 299, 'label' => 'sale', 'colors' => ['black', 'pink'],
                'released_on' => '2025-05-15', 'in_stock' => 'Y',
                'preview' => '<p>Noise-cancelling headphones that fold into a jacket pocket.</p>',
                'detail' => '<p>Three levels of noise cancelling, 40 hours of music, and a transparency mode for announcements at the station.</p>',
                'features' => ['40 hours of music', 'Noise cancelling', 'Folds flat'],
                'package' => "Headphones\nCase\nUSB-C cable",
            ],
            'pulse-mini' => [
                'section' => 'speakers', 'brand' => 'vantor', 'name' => 'Pulse Mini', 'sort' => 500,
                'picture' => 'speaker.svg', 'article' => 'VT-PM', 'price' => 89, 'colors' => ['blue', 'black'],
                'in_stock' => null,
                'preview' => '<p>A speaker the size of a can that is not afraid of rain.</p>',
                // Plain text, the way Bitrix keeps a text-type field: escaped, line breaks kept.
                'detail' => "Waterproof to IP67.\n12 hours on one charge.\nTwo of them play in stereo.",
                'detail_type' => 'text',
            ],
            'stride' => [
                'section' => 'wearables', 'brand' => 'orbita', 'name' => 'Stride', 'sort' => 600,
                'picture' => 'watch.svg', 'article' => 'OR-ST', 'price' => 249, 'label' => 'new', 'colors' => ['black', 'silver'],
                'released_on' => '2026-06-01', 'in_stock' => 'Y',
                'preview' => '<p>A watch with a week of battery and a screen you can read in the sun.</p>',
            ],
            'gift-card' => [
                'section' => null, 'brand' => null, 'name' => 'Gift card', 'sort' => 700,
                'picture' => 'gift-card.svg', 'price' => 50,
                'preview' => '<p>Not sure what to give? Let them choose. Lives right under the catalog, outside any section.</p>',
            ],
        ];

        $created = [];

        foreach ($products as $code => $data) {
            $product = Product::query()->create([
                'section_id' => $data['section'] === null ? null : $sections[$data['section']]->id,
                'brand_id' => $this->brand($data['brand']),
                'name' => $data['name'],
                'code' => $code,
                'sort' => $data['sort'],
                'active_from' => now()->subMonths(2),
                'preview_picture' => $this->path($data['picture']),
                'preview_text' => $data['preview'],
                'detail_picture' => isset($data['detail']) ? $this->path($data['picture']) : null,
                'detail_text' => $data['detail'] ?? null,
                'detail_text_type' => $data['detail_type'] ?? 'html',
                'article' => $data['article'] ?? null,
                'price' => $data['price'],
                'label' => $data['label'] ?? null,
                'colors' => $data['colors'] ?? null,
                'released_on' => $data['released_on'] ?? null,
                'in_stock' => $data['in_stock'] ?? null,
                'features' => $data['features'] ?? null,
                'specs' => $data['specs'] ?? null,
                'package' => $data['package'] ?? null,
                'photos' => isset($data['photos']) ? array_map($this->path(...), $data['photos']) : null,
                'manual' => isset($data['manual']) ? $this->path($data['manual']) : null,
            ]);

            if (isset($data['seo'])) {
                $product->updateSeoMeta($data['seo']);
            }

            if (isset($data['manual'])) {
                $product->attachFile('manual', $this->path($data['manual']), ['filename' => 'Aero 14 quick start.pdf']);
            }

            $created[$code] = $product;
        }

        // Shown as links on the product page.
        $created['aero-14']->related()->attach([
            $created['quiet-one']->id => ['sort' => 1],
            $created['pulse-mini']->id => ['sort' => 2],
        ]);

        $this->stores($created);

        // Retired: kept in the admin, gone from the site.
        Product::query()->create(['section_id' => $sections['laptops']->id, 'name' => 'Aero 13 (2023)', 'code' => 'aero-13', 'active' => false, 'sort' => 900, 'price' => 899]);
    }

    private function brand(?string $code): ?int
    {
        if ($code === null || ! Soft::available(Brand::class)) {
            return null;
        }

        $id = Brand::query()->where('code', $code)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string,Product>  $products
     */
    private function stores(array $products): void
    {
        if (! Soft::available(Store::class)) {
            return;
        }

        $stores = Store::query()->pluck('id', 'code');
        $everywhere = $stores->values()->all();
        $centre = $stores->only('city-centre')->values()->all();

        foreach ($products as $code => $product) {
            $product->stores()->attach(in_array($code, ['aero-14', 'quiet-one', 'pulse-mini'], true) ? $everywhere : $centre);
        }
    }
}
