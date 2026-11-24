<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Feed;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Support\MediaUrl;
use Atrium\Admin\Support\RecordUrls;
use Atrium\Admin\Support\Sites;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * The catalog as a Yandex Market feed (YML, `yml_catalog`): the shop, its
 * currency, the sections as categories and every product as an offer.
 *
 * The feed is written as it is read: products come from the database in
 * chunks of `$chunk` and go to the output after each one, so a catalog of any
 * size needs the memory of one chunk. Only the sections are kept, they are
 * the categories.
 *
 * What Market requires of an offer is left to the product: one without a
 * price, an address or an active section is not listed (Market would refuse
 * it); one without a picture is, and Market says so in its report.
 */
final class MarketFeed
{
    /** What Market allows: pictures of one offer. */
    private const MAX_PICTURES = 10;

    /** What Market allows: the length of the shop's short name and of a description. */
    public const NAME_LENGTH = 20;

    private const DESCRIPTION_LENGTH = 3000;

    public function __construct(
        private readonly RecordUrls $urls,
        private readonly Sites $sites,
        private readonly int $chunk = 500,
    ) {}

    /**
     * Write the feed to an address a stream can be opened on (`php://output`).
     */
    public function write(string $uri, string $language): void
    {
        $site = $this->sites->current();
        $categories = $this->categories();
        $xml = new XMLWriter;
        $xml->openUri($uri);
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('yml_catalog');
        $xml->writeAttribute('date', now()->format('Y-m-d H:i'));
        $xml->startElement('shop');
        $this->text($xml, 'name', $this->setting('market_name', Str::limit($site->name, self::NAME_LENGTH, '')));
        $this->text($xml, 'company', $this->setting('market_company', $site->name));
        $this->text($xml, 'url', $site->url());
        $currency = $this->setting('market_currency', 'RUB');
        $xml->startElement('currencies');
        $xml->startElement('currency');
        $xml->writeAttribute('id', $currency);
        $xml->writeAttribute('rate', '1');
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('categories');

        foreach ($categories as $id => [$parent, $name]) {
            $xml->startElement('category');
            $xml->writeAttribute('id', (string) $id);

            if ($parent !== null) {
                $xml->writeAttribute('parentId', (string) $parent);
            }

            $xml->text($this->clean($name));
            $xml->endElement();
        }

        $xml->endElement();
        $xml->startElement('offers');

        foreach ($this->products(array_keys($categories))->lazyById($this->chunk) as $product) {
            if (($url = $this->urls->url($product, $language)) !== null) {
                $this->offer($xml, $product, $url, $currency);
            }
        }

        $xml->endDocument();
    }

    /**
     * The sections that are on the site, from the top down, the parents
     * before their children: id => [parent id, name]. A section is on only
     * when every section above it is: one under a section that is off, or
     * whose parent is gone, or that is its own ancestor, is left out.
     *
     * @return array<int,array{0:?int,1:string}>
     */
    private function categories(): array
    {
        $rows = CatalogSection::query()->where('active', true)->orderBy('sort')->orderBy('id')->get(['id', 'parent_id', 'name'])->keyBy('id');
        $depths = [];

        foreach ($rows as $id => $row) {
            $depth = $this->depth($rows, $row);

            if ($depth !== null) {
                $depths[$id] = $depth;
            }
        }

        asort($depths);

        $categories = [];

        foreach (array_keys($depths) as $id) {
            $categories[$id] = [$rows[$id]->parent_id, $rows[$id]->name];
        }

        return $categories;
    }

    /**
     * How many sections are above this one, or null when the way up does not
     * end at a top-level section on the site.
     *
     * @param  Collection<int,CatalogSection>  $rows
     */
    private function depth(Collection $rows, CatalogSection $section): ?int
    {
        $seen = [];

        while ($section->parent_id !== null) {
            $section = $rows[$section->parent_id] ?? null;

            if ($section === null || in_array($section->id, $seen, true)) {
                return null;
            }

            $seen[] = $section->id;
        }

        return count($seen);
    }

    /**
     * @param  array<int,int>  $sections  the sections that are on the site
     * @return Builder<Product>
     */
    private function products(array $sections): Builder
    {
        return Product::query()
            ->with('brand')
            ->where('active', true)
            ->whereIn('section_id', $sections)
            ->where(fn (Builder $query) => $query->whereNull('active_from')->orWhere('active_from', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('active_to')->orWhere('active_to', '>=', now()))
            ->where('price', '>', 0);
    }

    private function offer(XMLWriter $xml, Product $product, string $url, string $currency): void
    {
        $xml->startElement('offer');
        $xml->writeAttribute('id', (string) $product->id);
        $xml->writeAttribute('available', $product->in_stock === 'Y' ? 'true' : 'false');
        $this->text($xml, 'url', $url);
        $this->text($xml, 'price', (string) $product->price);
        $this->text($xml, 'currencyId', $currency);
        $this->text($xml, 'categoryId', (string) $product->section_id);

        foreach ($this->pictures($product) as $picture) {
            $this->text($xml, 'picture', $picture);
        }

        $this->text($xml, 'name', $product->name);

        $brand = $product->brand?->getAttribute('name');

        if (is_string($brand) && $brand !== '') {
            $this->text($xml, 'vendor', $brand);
        }

        if ((string) $product->article !== '') {
            $this->text($xml, 'vendorCode', (string) $product->article);
        }

        if (($description = $this->description($product)) !== '') {
            $this->text($xml, 'description', $description);
        }

        $xml->endElement();
    }

    /**
     * The main picture first, then the preview and the gallery: no repeats,
     * at most what Market takes.
     *
     * @return array<string,string>
     */
    private function pictures(Product $product): array
    {
        $pictures = [];

        foreach ([$product->detail_picture, $product->preview_picture, ...(array) $product->photos] as $path) {
            if (($url = MediaUrl::to($path)) !== null) {
                $pictures[$url] = $url;
            }
        }

        return array_slice($pictures, 0, self::MAX_PICTURES);
    }

    /**
     * The preview text, the full text when there is none, as plain text.
     */
    private function description(Product $product): string
    {
        $text = (string) ($product->preview_text ?: $product->detail_text);
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $text)), self::DESCRIPTION_LENGTH, '');
    }

    /**
     * What the owner set for the feed on this site, the fallback when it is empty.
     */
    private function setting(string $key, string $fallback): string
    {
        $value = Setting::get('catalog.'.$key);

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function text(XMLWriter $xml, string $name, string $value): void
    {
        $xml->writeElement($name, $this->clean($value));
    }

    /**
     * Characters XML does not allow are dropped.
     */
    private function clean(string $value): string
    {
        return (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value);
    }
}
