<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\Language;
use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\RecordUrls;
use Atrium\Admin\Support\Sites;
use Atrium\Brands\Models\Brand;
use Atrium\Catalog\Feed\MarketFeed;
use Atrium\Catalog\Models\CatalogSection;
use Atrium\Catalog\Models\Product;
use Atrium\Catalog\Tests\TestCase;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;

/**
 * The Yandex Market feed of the catalog: what it lists, for which site, and
 * that what comes out meets what Market requires of a YML file.
 */
final class MarketFeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['en' => 'English', 'de' => 'Deutsch'] as $code => $name) {
            Language::query()->firstOrCreate(['code' => $code], ['name' => $name, 'active' => true, 'sort' => 100 + Language::query()->count()]);
        }

        app(Sites::class)->default();
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function section(string $name, array $attributes = []): CatalogSection
    {
        return CatalogSection::query()->create($attributes + ['name' => $name, 'code' => str($name)->slug()->toString(), 'active' => true, 'sort' => 500]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    private function product(string $name, ?CatalogSection $section, array $attributes = []): Product
    {
        return Product::query()->create($attributes + [
            'name' => $name,
            'code' => str($name)->slug()->toString(),
            'section_id' => $section?->id,
            'active' => true,
            'price' => 100,
            'in_stock' => 'Y',
            'sort' => 500,
        ]);
    }

    private function feed(string $url = '/catalog.xml'): DOMDocument
    {
        $response = $this->get($url)->assertOk();
        $this->assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));

        return $this->parse($response->streamedContent());
    }

    private function parse(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING), 'the feed is well-formed XML');

        return $document;
    }

    /**
     * @return array<int,string>
     */
    private function values(DOMDocument $document, string $path): array
    {
        return array_map(
            static fn ($node): string => (string) $node->textContent,
            iterator_to_array((new DOMXPath($document))->query($path) ?: [], false),
        );
    }

    private function value(DOMDocument $document, string $path): string
    {
        return (string) (new DOMXPath($document))->evaluate('string('.$path.')');
    }

    private function offer(DOMDocument $document, Product $product): DOMElement
    {
        $offer = (new DOMXPath($document))->query('/yml_catalog/shop/offers/offer[@id="'.$product->id.'"]')?->item(0);
        $this->assertInstanceOf(DOMElement::class, $offer, 'the product is an offer');

        return $offer;
    }

    /**
     * What Market requires of a YML file (yandex.com/support/partnermarket/export/yml.html):
     * the shop's elements in their order, categories that exist and have
     * their parents, and in every offer an id, a way to tell it is on sale,
     * an address, a positive price in the currency of the shop, a category
     * and a name; pictures within the limits.
     */
    private function assertMeetsMarket(DOMDocument $document): void
    {
        $root = $document->documentElement;
        $this->assertSame('yml_catalog', $root?->nodeName);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', (string) $root?->getAttribute('date'));
        $this->assertSame('UTF-8', $document->encoding);

        $shop = (new DOMXPath($document))->query('/yml_catalog/shop')?->item(0);
        $this->assertInstanceOf(DOMElement::class, $shop);
        $order = [];

        foreach ($shop->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $order[] = $child->nodeName;
            }
        }

        $this->assertSame(['name', 'company', 'url', 'currencies', 'categories', 'offers'], $order);
        $this->assertLessThanOrEqual(20, mb_strlen($this->value($document, '/yml_catalog/shop/name')));
        $this->assertNotSame('', $this->value($document, '/yml_catalog/shop/company'));
        $this->assertMatchesRegularExpression('#^https?://[^/]+#', $this->value($document, '/yml_catalog/shop/url'));

        $currency = $this->value($document, '/yml_catalog/shop/currencies/currency/@id');
        $this->assertContains($currency, ['RUB', 'BYN', 'KZT', 'UAH', 'USD', 'EUR']);
        $this->assertSame('1', $this->value($document, '/yml_catalog/shop/currencies/currency/@rate'));

        $ids = $this->values($document, '/yml_catalog/shop/categories/category/@id');
        $this->assertSame($ids, array_values(array_unique($ids)), 'category ids are unique');

        foreach ((new DOMXPath($document))->query('/yml_catalog/shop/categories/category[@parentId]') ?: [] as $category) {
            $parent = $category->getAttribute('parentId');
            $this->assertContains($parent, $ids, 'a category\'s parent is a category');
            $this->assertLessThan(array_search($category->getAttribute('id'), $ids, true), array_search($parent, $ids, true), 'the parent comes first');
        }

        $offerIds = $this->values($document, '/yml_catalog/shop/offers/offer/@id');
        $this->assertSame($offerIds, array_values(array_unique($offerIds)), 'offer ids are unique');

        foreach ((new DOMXPath($document))->query('/yml_catalog/shop/offers/offer') ?: [] as $offer) {
            $xpath = new DOMXPath($document);
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{1,20}$/', $offer->getAttribute('id'));
            $this->assertContains($offer->getAttribute('available'), ['true', 'false']);
            $this->assertMatchesRegularExpression('#^https?://\S{1,500}$#', (string) $xpath->evaluate('string(url)', $offer));
            $this->assertGreaterThan(0, (float) $xpath->evaluate('string(price)', $offer));
            $this->assertSame($currency, $xpath->evaluate('string(currencyId)', $offer));
            $this->assertContains($xpath->evaluate('string(categoryId)', $offer), $ids);
            $this->assertNotSame('', $xpath->evaluate('string(name)', $offer));
            $this->assertLessThanOrEqual(10, (int) $xpath->evaluate('count(picture)', $offer));
            $this->assertLessThanOrEqual(3000, mb_strlen((string) $xpath->evaluate('string(description)', $offer)));

            foreach ($xpath->query('picture', $offer) ?: [] as $picture) {
                $this->assertMatchesRegularExpression('#^https?://\S{1,500}$#', (string) $picture->textContent);
            }
        }
    }

    public function test_it_is_a_yml_catalog_with_the_shop_its_categories_and_the_offers(): void
    {
        $brand = Brand::query()->create(['name' => 'Aero', 'code' => 'aero', 'active' => true, 'sort' => 500]);
        $laptops = $this->section('Laptops');
        $gaming = $this->section('Gaming', ['parent_id' => $laptops->id]);
        $product = $this->product('Aero 14', $gaming, [
            'price' => 1099.5,
            'article' => 'AE14',
            'brand_id' => $brand->id,
            'preview_text' => '<p>Light &amp; fast.</p>',
            'detail_picture' => '/storage/products/aero-14.jpg',
        ]);

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame('Demo shop', $this->value($document, '/yml_catalog/shop/name'));
        $this->assertSame('Demo shop', $this->value($document, '/yml_catalog/shop/company'));
        $this->assertSame($this->root(), $this->value($document, '/yml_catalog/shop/url'));
        $this->assertSame('RUB', $this->value($document, '/yml_catalog/shop/currencies/currency/@id'));
        $this->assertSame(now()->format('Y-m-d H:i'), $document->documentElement?->getAttribute('date'));

        $this->assertSame(['Laptops', 'Gaming'], $this->values($document, '/yml_catalog/shop/categories/category'));
        $this->assertSame('', $this->value($document, '/yml_catalog/shop/categories/category[1]/@parentId'));
        $this->assertSame((string) $laptops->id, $this->value($document, '/yml_catalog/shop/categories/category[2]/@parentId'));

        $offer = $this->offer($document, $product);
        $xpath = new DOMXPath($document);
        $this->assertSame('true', $offer->getAttribute('available'));
        $this->assertSame($this->root().'/catalog/laptops/gaming/aero-14', $xpath->evaluate('string(url)', $offer));
        $this->assertSame('1099.50', $xpath->evaluate('string(price)', $offer));
        $this->assertSame('RUB', $xpath->evaluate('string(currencyId)', $offer));
        $this->assertSame((string) $gaming->id, $xpath->evaluate('string(categoryId)', $offer));
        $this->assertSame($this->root().'/storage/products/aero-14.jpg', $xpath->evaluate('string(picture)', $offer));
        $this->assertSame('Aero 14', $xpath->evaluate('string(name)', $offer));
        $this->assertSame('Aero', $xpath->evaluate('string(vendor)', $offer));
        $this->assertSame('AE14', $xpath->evaluate('string(vendorCode)', $offer));
        $this->assertSame('Light & fast.', $xpath->evaluate('string(description)', $offer));
    }

    public function test_only_products_on_sale_now_are_offers(): void
    {
        $section = $this->section('Laptops');
        $this->product('Listed', $section);
        $this->product('Window open', $section, ['active_from' => now()->subDay(), 'active_to' => now()->addDay()]);
        $this->product('Switched off', $section, ['active' => false]);
        $this->product('Not yet', $section, ['active_from' => now()->addDay()]);
        $this->product('Already over', $section, ['active_to' => now()->subDay()]);

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame(['Listed', 'Window open'], $this->values($document, '/yml_catalog/shop/offers/offer/name'));
    }

    public function test_what_market_would_refuse_is_not_listed(): void
    {
        $section = $this->section('Laptops');
        $off = $this->section('Off', ['active' => false]);
        $under = $this->section('Under', ['parent_id' => $off->id]);
        $this->product('Listed', $section);
        $this->product('Without a price', $section, ['price' => null]);
        $this->product('Free', $section, ['price' => 0]);
        $this->product('Without a section', null);
        $this->product('In a section that is off', $off);
        $this->product('Under a section that is off', $under);
        $this->product('In a section that is gone', null, ['section_id' => 9999]);

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame(['Listed'], $this->values($document, '/yml_catalog/shop/offers/offer/name'));
    }

    public function test_categories_are_the_sections_on_the_site_parents_first(): void
    {
        $b = $this->section('Bravo', ['sort' => 20]);
        $a = $this->section('Alpha', ['sort' => 10]);
        $child = $this->section('Child of Bravo', ['parent_id' => $b->id, 'sort' => 1]);
        $grandchild = $this->section('Grandchild', ['parent_id' => $child->id, 'sort' => 1]);
        $this->section('Off', ['active' => false]);
        $this->section('Orphan', ['parent_id' => 9999]);
        $this->product('Offer', $grandchild);

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame(['Alpha', 'Bravo', 'Child of Bravo', 'Grandchild'], $this->values($document, '/yml_catalog/shop/categories/category'));
        $this->assertSame(['', '', (string) $b->id, (string) $child->id], array_map(
            fn ($category): string => $category->getAttribute('parentId'),
            iterator_to_array((new DOMXPath($document))->query('/yml_catalog/shop/categories/category') ?: [], false),
        ));
        $this->assertNotSame((string) $a->id, $this->value($document, '/yml_catalog/shop/categories/category[2]/@id'));
    }

    public function test_sections_that_are_their_own_ancestors_are_left_out(): void
    {
        $self = $this->section('Itself');
        $self->forceFill(['parent_id' => $self->id])->save();
        $one = $this->section('One');
        $two = $this->section('Two', ['parent_id' => $one->id]);
        $one->forceFill(['parent_id' => $two->id])->save();
        $fine = $this->section('Fine');
        $this->product('Offer', $fine);
        $this->product('Lost', $self);

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame(['Fine'], $this->values($document, '/yml_catalog/shop/categories/category'));
        $this->assertSame(['Offer'], $this->values($document, '/yml_catalog/shop/offers/offer/name'));
    }

    public function test_pictures_come_main_first_without_repeats_and_within_the_limit(): void
    {
        $section = $this->section('Laptops');
        $many = $this->product('Many', $section, [
            'detail_picture' => 'main.jpg',
            'preview_picture' => 'main.jpg',
            'photos' => ['two.jpg', 'https://cdn.example.com/three.jpg', '', null, ['url' => '/four.jpg'], 'five.jpg', 'six.jpg', 'seven.jpg', 'eight.jpg', 'nine.jpg', 'ten.jpg', 'eleven.jpg'],
        ]);
        $preview = $this->product('Preview only', $section, ['preview_picture' => '/storage/preview.jpg']);
        $none = $this->product('None', $section);

        $document = $this->feed();
        $this->assertMeetsMarket($document);
        $xpath = new DOMXPath($document);

        $this->assertSame([
            $this->root().'/storage/main.jpg',
            $this->root().'/storage/two.jpg',
            'https://cdn.example.com/three.jpg',
            $this->root().'/four.jpg',
            $this->root().'/storage/five.jpg',
            $this->root().'/storage/six.jpg',
            $this->root().'/storage/seven.jpg',
            $this->root().'/storage/eight.jpg',
            $this->root().'/storage/nine.jpg',
            $this->root().'/storage/ten.jpg',
        ], array_map(static fn ($node): string => (string) $node->textContent, iterator_to_array($xpath->query('picture', $this->offer($document, $many)) ?: [], false)));
        $this->assertSame($this->root().'/storage/preview.jpg', $xpath->evaluate('string(picture)', $this->offer($document, $preview)));
        $this->assertSame(0, (int) $xpath->evaluate('count(picture)', $this->offer($document, $none)));
        $this->assertSame('None', $xpath->evaluate('string(name)', $this->offer($document, $none)));
    }

    public function test_available_means_in_stock(): void
    {
        $section = $this->section('Laptops');
        $in = $this->product('In', $section, ['in_stock' => 'Y']);
        $out = $this->product('Out', $section, ['in_stock' => 'N']);
        $unknown = $this->product('Unknown', $section, ['in_stock' => null]);

        $document = $this->feed();

        $this->assertSame('true', $this->offer($document, $in)->getAttribute('available'));
        $this->assertSame('false', $this->offer($document, $out)->getAttribute('available'));
        $this->assertSame('false', $this->offer($document, $unknown)->getAttribute('available'));
    }

    public function test_the_vendor_is_the_brand_when_there_is_one(): void
    {
        $brand = Brand::query()->create(['name' => 'Aero', 'code' => 'aero', 'active' => true, 'sort' => 500]);
        $section = $this->section('Laptops');
        $with = $this->product('With brand', $section, ['brand_id' => $brand->id, 'article' => 'A-1']);
        $gone = $this->product('Brand gone', $section, ['brand_id' => 9999, 'article' => '']);
        $without = $this->product('Without brand', $section);

        $document = $this->feed();
        $xpath = new DOMXPath($document);

        $this->assertSame('Aero', $xpath->evaluate('string(vendor)', $this->offer($document, $with)));
        $this->assertSame('A-1', $xpath->evaluate('string(vendorCode)', $this->offer($document, $with)));
        $this->assertSame(0, (int) $xpath->evaluate('count(vendor) + count(vendorCode)', $this->offer($document, $gone)));
        $this->assertSame(0, (int) $xpath->evaluate('count(vendor) + count(vendorCode)', $this->offer($document, $without)));
    }

    public function test_the_description_is_plain_text_from_the_preview_or_the_full_text(): void
    {
        $section = $this->section('Laptops');
        $preview = $this->product('Preview', $section, ['preview_text' => '<p>First</p><p>Second &amp; third</p><p>It&#039;s</p>', 'detail_text' => 'Not this.']);
        $detail = $this->product('Detail', $section, ['preview_text' => '', 'detail_text' => "Line one\n\n   line   two"]);
        $long = $this->product('Long', $section, ['preview_text' => str_repeat('a', 3500)]);
        $none = $this->product('None', $section);

        $document = $this->feed();
        $this->assertMeetsMarket($document);
        $xpath = new DOMXPath($document);

        $this->assertSame('First Second & third It\'s', $xpath->evaluate('string(description)', $this->offer($document, $preview)));
        $this->assertSame('Line one line two', $xpath->evaluate('string(description)', $this->offer($document, $detail)));
        $this->assertSame(str_repeat('a', 3000), $xpath->evaluate('string(description)', $this->offer($document, $long)));
        $this->assertSame(0, (int) $xpath->evaluate('count(description)', $this->offer($document, $none)));
    }

    public function test_what_xml_does_not_allow_is_escaped_or_dropped(): void
    {
        $section = $this->section('Tom & Jerry <b>');
        $product = $this->product('Tom & "Jerry" <b>x</b>', $section, ['article' => "A\x01\x0B1", 'preview_text' => "Fish\x08 & chips"]);

        $response = $this->get('/catalog.xml')->assertOk();
        $xml = $response->streamedContent();
        $document = $this->parse($xml);
        $xpath = new DOMXPath($document);

        $this->assertStringContainsString('Tom &amp; Jerry &lt;b&gt;', $xml);
        $this->assertSame('Tom & Jerry <b>', $this->value($document, '/yml_catalog/shop/categories/category'));
        $this->assertSame('Tom & "Jerry" <b>x</b>', $xpath->evaluate('string(name)', $this->offer($document, $product)));
        $this->assertSame('A1', $xpath->evaluate('string(vendorCode)', $this->offer($document, $product)));
        $this->assertSame('Fish & chips', $xpath->evaluate('string(description)', $this->offer($document, $product)));
    }

    public function test_the_shop_is_what_the_owner_set_for_this_site(): void
    {
        $this->product('Offer', $this->section('Laptops'));
        Setting::save('catalog', ['market_name' => 'Aero Store', 'market_company' => 'Aero Trading LLC', 'market_currency' => 'BYN'], app(Sites::class)->default());

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame('Aero Store', $this->value($document, '/yml_catalog/shop/name'));
        $this->assertSame('Aero Trading LLC', $this->value($document, '/yml_catalog/shop/company'));
        $this->assertSame('BYN', $this->value($document, '/yml_catalog/shop/currencies/currency/@id'));
        $this->assertSame(['BYN'], $this->values($document, '/yml_catalog/shop/offers/offer/currencyId'));
    }

    public function test_empty_settings_fall_back_to_the_site_and_the_ruble(): void
    {
        $this->product('Offer', $this->section('Laptops'));
        Setting::save('catalog', ['market_name' => '', 'market_company' => '', 'market_currency' => ''], app(Sites::class)->default());
        app(Sites::class)->default()->forceFill(['name' => 'The shop of the best laptops in town'])->save();

        $document = $this->feed();
        $this->assertMeetsMarket($document);

        $this->assertSame('The shop of the best', $this->value($document, '/yml_catalog/shop/name'));
        $this->assertSame(20, mb_strlen($this->value($document, '/yml_catalog/shop/name')));
        $this->assertSame('The shop of the best laptops in town', $this->value($document, '/yml_catalog/shop/company'));
        $this->assertSame('RUB', $this->value($document, '/yml_catalog/shop/currencies/currency/@id'));
    }

    public function test_another_site_has_its_own_shop_and_addresses(): void
    {
        $outlet = Site::query()->create(['code' => 'outlet', 'name' => 'Outlet', 'domains' => ['outlet.localhost'], 'languages' => ['en'], 'default_language' => 'en', 'active' => true, 'sort' => 50]);
        $this->product('Offer', $this->section('Laptops'));
        Setting::save('catalog', ['market_name' => 'Main shop', 'market_currency' => 'EUR'], app(Sites::class)->default());
        Setting::save('catalog', ['market_name' => 'Outlet shop', 'market_company' => 'Outlet Ltd'], $outlet);

        $main = $this->feed();
        $this->assertSame('Main shop', $this->value($main, '/yml_catalog/shop/name'));
        $this->assertSame('EUR', $this->value($main, '/yml_catalog/shop/currencies/currency/@id'));
        $this->assertSame($this->root().'/catalog/laptops/offer', $this->values($main, '/yml_catalog/shop/offers/offer/url')[0]);

        $other = $this->feed('http://outlet.localhost/catalog.xml');
        $this->assertMeetsMarket($other);
        $this->assertSame('Outlet shop', $this->value($other, '/yml_catalog/shop/name'));
        $this->assertSame('Outlet Ltd', $this->value($other, '/yml_catalog/shop/company'));
        $this->assertSame('RUB', $this->value($other, '/yml_catalog/shop/currencies/currency/@id'));
        $this->assertSame('http://outlet.localhost', $this->value($other, '/yml_catalog/shop/url'));
        $this->assertSame('http://outlet.localhost/catalog/laptops/offer', $this->values($other, '/yml_catalog/shop/offers/offer/url')[0]);
    }

    public function test_a_language_has_its_own_address(): void
    {
        $this->product('Offer', $this->section('Laptops'));

        $document = $this->feed('/de/catalog.xml');
        $this->assertMeetsMarket($document);

        $this->assertSame($this->root().'/de/catalog/laptops/offer', $this->values($document, '/yml_catalog/shop/offers/offer/url')[0]);
        $this->get('/xx/catalog.xml')->assertNotFound();
    }

    public function test_a_large_catalog_is_read_in_chunks_and_written_whole(): void
    {
        $section = $this->section('Laptops');

        foreach (range(1, 5) as $number) {
            $this->product('Offer '.$number, $section);
        }

        $feed = new MarketFeed($this->app->make(RecordUrls::class), $this->app->make(Sites::class), 2);
        $file = tempnam(sys_get_temp_dir(), 'market');
        DB::enableQueryLog();
        $feed->write($file, 'en');
        $reads = $this->productReads();
        $xml = (string) file_get_contents($file);
        unlink($file);

        $this->assertCount(3, $reads, 'five products in chunks of two are three reads');
        $document = $this->parse($xml);
        $this->assertMeetsMarket($document);
        $this->assertSame(['Offer 1', 'Offer 2', 'Offer 3', 'Offer 4', 'Offer 5'], $this->values($document, '/yml_catalog/shop/offers/offer/name'));
    }

    public function test_an_empty_catalog_is_a_valid_feed_without_offers(): void
    {
        $document = $this->feed();

        $this->assertSame(0, (int) (new DOMXPath($document))->evaluate('count(/yml_catalog/shop/offers/offer)'));
        $this->assertSame(1, (int) (new DOMXPath($document))->evaluate('count(/yml_catalog/shop/offers)'));
    }

    public function test_the_route_has_its_names(): void
    {
        $this->assertSame($this->root().'/catalog.xml', route('atrium.catalog.market'));
        $this->assertSame($this->root().'/de/catalog.xml', route('localized.atrium.catalog.market', ['locale' => 'de']));
    }

    public function test_the_xml_is_indented_and_has_its_declaration(): void
    {
        $this->product('Offer', $this->section('Laptops'));

        $content = $this->get('/catalog.xml')->assertOk()->streamedContent();

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString("\n <shop>\n  <name>", $content);
        $this->assertStringEndsWith("</yml_catalog>\n", $content);
    }

    public function test_the_products_are_read_five_hundred_at_a_time(): void
    {
        $this->product('Offer', $this->section('Laptops'));

        DB::enableQueryLog();
        $this->get('/catalog.xml')->assertOk()->streamedContent();
        $reads = $this->productReads();

        $this->assertNotEmpty($reads);
        $this->assertStringContainsString('limit 500', $reads[0]);
    }

    public function test_a_price_below_one_is_a_price(): void
    {
        $section = $this->section('Laptops');
        $cheap = $this->product('Cheap', $section, ['price' => 0.5]);
        $one = $this->product('One', $section, ['price' => 1]);

        $document = $this->feed();

        $this->assertSame('0.50', (new DOMXPath($document))->evaluate('string(price)', $this->offer($document, $cheap)));
        $this->assertSame('1.00', (new DOMXPath($document))->evaluate('string(price)', $this->offer($document, $one)));
    }

    /**
     * The logged queries that read products, whichever way the database quotes a name.
     *
     * @return list<string>
     */
    private function productReads(): array
    {
        return array_values(array_filter(
            array_column(DB::getQueryLog(), 'query'),
            static fn (string $query): bool => preg_match('/from\W+catalog_products\W/', $query) === 1,
        ));
    }
}
