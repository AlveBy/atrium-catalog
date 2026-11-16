<?php

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature;

use Atrium\Catalog\Tests\TestCase;

/**
 * The demo shop of `atrium:install --demo`, as a visitor sees it: the theme
 * draws modules it has no template for, in three languages, on two sites.
 */
final class DemoSiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDemo();
    }

    public function test_the_home_page_shows_the_shop(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Products', 'Aero 14', 'Stores', 'City centre', 'News', 'A new store in the city centre'])
            ->assertDontSee('Aero 13');
    }

    public function test_a_product_page_shows_every_kind_of_property(): void
    {
        $this->get('/catalog/computers/laptops/aero-14')
            ->assertOk()
            ->assertSee('<title>Aero 14 laptop: 1.2 kg, 18 hours of battery | Demo shop</title>', false)
            ->assertSeeInOrder(['Products', 'Computers', 'Laptops', '<h1>Aero 14</h1>'], false)
            ->assertSee('The aluminium case is 14 mm thin')
            ->assertSeeInOrder(['LM-A14', '1199.00', 'Lumen', 'Bestseller', 'Silver, Blue', 'March 12, 2026', '18 hours of battery, 1.2 kg, Matte 2.8K screen'])
            ->assertSee('<dd><a href="'.$this->root().'/catalog/audio/headphones/quiet-one">Quiet One</a>, <a href="'.$this->root().'/catalog/audio/speakers/pulse-mini">Pulse Mini</a></dd>', false)
            // The Brands module is installed: the brand is a property.
            ->assertSee('>Lumen<', false)
            ->assertSee('<th>Screen</th>', false)
            ->assertSee('65 W USB-C charger<br />', false)
            ->assertSee('src="'.$this->root().'/storage/demo/laptop-open.svg"', false)
            ->assertSee('/storage/demo/quick-start.pdf" download>', false)
            // The Stores module is installed: the product says where to buy it.
            ->assertSee('<dd><a href="'.$this->root().'/stores/city-centre">City centre</a>, <a href="'.$this->root().'/stores/riverside-outlet">Riverside outlet</a></dd>', false);
    }

    public function test_plain_text_and_records_outside_sections(): void
    {
        $this->get('/catalog/audio/speakers/pulse-mini')->assertOk()->assertSee('Waterproof to IP67.<br />', false);
        $this->get('/catalog/gift-card')->assertOk()->assertSee('<h1>Gift card</h1>', false);
        $this->get('/catalog/computers/laptops/aero-13')->assertNotFound();
    }

    public function test_news_are_in_the_languages_they_are_written_in(): void
    {
        $this->get('/news/a-new-store-in-the-city-centre')
            ->assertOk()
            ->assertSee('<link rel="alternate" hreflang="ru" href="'.$this->root().'/ru/news/novyj-magazin-v-centre">', false);

        $this->get('/de/news/neue-filiale-in-der-innenstadt')->assertOk()->assertSee('<html lang="de">', false);
        $this->get('/news/how-to-choose-a-laptop-for-study')->assertOk();
        $this->get('/de/news')->assertOk()->assertSee('Herbstaktion')->assertDontSee('How to choose');
    }

    public function test_the_outlet_is_a_site_of_its_own_in_german(): void
    {
        $this->get('/news/outlet-opening')->assertNotFound();

        $this->get('http://outlet.localhost/news/outlet-eroeffnung')
            ->assertOk()
            ->assertSee('<html lang="de">', false)
            ->assertSee('<h1>Das Outlet öffnet am Samstag</h1>', false)
            ->assertSee('Atrium Outlet');

        $this->get('http://outlet.localhost/news')->assertOk()->assertDontSee('Herbstaktion');
    }

    public function test_a_store_page_shows_its_address_and_phones(): void
    {
        $this->get('/stores/city-centre')
            ->assertOk()
            ->assertSee('+1 555 0100, +1 555 0101')
            ->assertSee('centre@example.com');
    }
}
