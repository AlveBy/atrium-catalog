<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Feature\Import;

use Atrium\Admin\Models\MediaCollection;
use Atrium\Admin\Models\MediaItem;
use Atrium\Catalog\Import\Hosts;
use Atrium\Catalog\Import\Images;
use Atrium\Catalog\Import\RowProblem;
use Atrium\Catalog\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * An image cell is a URL, downloaded into the media library once, or a path
 * to a file already on the site. Nothing is fetched from inside the network.
 */
final class ImagesTest extends TestCase
{
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    private string $public;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->public = sys_get_temp_dir().'/atrium-images-'.uniqid();
        File::ensureDirectoryExists($this->public.'/upload');
        $this->app->usePublicPath($this->public);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->public);

        parent::tearDown();
    }

    public function test_a_path_from_the_site_root_is_kept_when_the_file_is_there(): void
    {
        File::put($this->public.'/upload/1.jpg', 'x');

        $this->assertSame('/upload/1.jpg', $this->images()->one('/upload/1.jpg'));
        $this->assertSame('/upload/1.jpg', $this->images()->one('\\upload\\1.jpg'));
    }

    public function test_a_path_on_the_public_disk_is_kept_when_the_file_is_there(): void
    {
        Storage::disk('public')->put('catalog/2.PNG', 'x');

        $this->assertSame('catalog/2.PNG', $this->images()->one('catalog/2.PNG'));
    }

    public function test_a_disk_path_is_not_looked_for_in_the_site_root(): void
    {
        File::put($this->public.'/upload/3.jpg', 'x');

        $this->assertProblem('There is no image upload/3.jpg on the site.', 'upload/3.jpg');
    }

    public function test_a_root_path_is_not_looked_for_on_the_disk(): void
    {
        Storage::disk('public')->put('upload/4.jpg', 'x');

        $this->assertProblem('There is no image /upload/4.jpg on the site.', '/upload/4.jpg');
    }

    /**
     * @return array<string,array{string}>
     */
    public static function forbiddenPaths(): array
    {
        return [
            'up a folder' => ['../secret.jpg'],
            'up a folder inside' => ['upload/../../secret.jpg'],
            'up a folder with backslashes' => ['upload\\..\\secret.jpg'],
            'a folder at the end' => ['upload/..'],
            'another scheme' => ['file:///etc/passwd.jpg'],
            'ftp' => ['ftp://example.com/1.jpg'],
            'a NUL byte' => ["upload/1.jpg\0.php"],
        ];
    }

    #[DataProvider('forbiddenPaths')]
    public function test_a_path_out_of_the_site_is_refused(string $path): void
    {
        $this->assertProblem('The image path is not allowed: '.$path, $path);
    }

    public function test_dots_inside_a_name_are_fine(): void
    {
        Storage::disk('public')->put('a..b/c..d.jpg', 'x');

        $this->assertSame('a..b/c..d.jpg', $this->images()->one('a..b/c..d.jpg'));
    }

    public function test_a_colon_after_the_start_is_not_a_scheme(): void
    {
        Storage::disk('public')->put('photos/10:30.jpg', 'x');

        $this->assertSame('photos/10:30.jpg', $this->images()->one('photos/10:30.jpg'));
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        Storage::disk('public')->put('price.pdf', 'x');

        $this->assertProblem('Not an image (JPEG, PNG, GIF, WebP or SVG): price.pdf', 'price.pdf');
        $this->assertProblem('Not an image (JPEG, PNG, GIF, WebP or SVG): photo', 'photo');
    }

    /**
     * @return array<string,array{string}>
     */
    public static function extensions(): array
    {
        return ['jpg' => ['jpg'], 'jpeg' => ['JPEG'], 'png' => ['png'], 'gif' => ['gif'], 'webp' => ['webp'], 'svg' => ['svg']];
    }

    #[DataProvider('extensions')]
    public function test_every_image_type_is_taken(string $extension): void
    {
        Storage::disk('public')->put('a.'.$extension, 'x');

        $this->assertSame('a.'.$extension, $this->images()->one('a.'.$extension));
    }

    public function test_an_address_that_is_not_one_is_refused(): void
    {
        $this->assertProblem('The image address is not valid: http://exa mple.com/1.jpg', 'http://exa mple.com/1.jpg');
    }

    public function test_an_address_inside_a_path_is_a_path(): void
    {
        $this->assertProblem('There is no image /upload/https://example.com/1.jpg on the site.', '/upload/https://example.com/1.jpg');
    }

    public function test_a_scheme_in_capitals_is_refused_too(): void
    {
        $this->assertProblem('The image path is not allowed: FILE:/etc/1.jpg', 'FILE:/etc/1.jpg');
    }

    public function test_an_image_without_redirects_is_asked_for_once(): void
    {
        Http::fake(['example.com/*' => Http::response(base64_decode(self::GIF))]);

        $this->images()->one('https://example.com/1.gif');

        Http::assertSentCount(1);
    }

    public function test_a_check_downloads_nothing(): void
    {
        $this->assertSame('HTTPS://example.com/1.jpg', $this->images(check: true)->one('HTTPS://example.com/1.jpg'));

        Http::assertNothingSent();
        $this->assertSame(0, MediaItem::query()->count());
    }

    public function test_a_check_still_refuses_an_address_that_is_not_one(): void
    {
        $this->expectExceptionObject(new RowProblem('The image address is not valid: http://'));

        $this->images(check: true)->one('http://');
    }

    public function test_an_image_is_downloaded_into_the_media_library(): void
    {
        $this->travelTo('2026-03-05 10:00:00');
        config(['atrium.locale' => 'ru']);
        Http::fake(['example.com/*' => Http::response(base64_decode(self::GIF))]);

        $url = $this->images()->one('https://example.com/photos/'.str_repeat('a', 300).'.gif?size=big');

        $this->assertMatchesRegularExpression('#^/storage/catalog/2026/03/[0-9a-f-]{36}\.gif$#', $url);
        $path = substr($url, strlen('/storage/'));
        Storage::disk('public')->assertExists($path);
        $this->assertSame(base64_decode(self::GIF), Storage::disk('public')->get($path));

        $item = MediaItem::query()->sole();
        $this->assertSame(str_repeat('a', 250), $item->name);
        $this->assertSame($url, $item->url);
        $this->assertSame('public', $item->disk);
        $this->assertSame($path, $item->path);
        $this->assertSame('gif', $item->extension);
        $this->assertSame('image/gif', $item->mime_type);
        $this->assertSame(strlen(base64_decode(self::GIF)), (int) $item->size);
        $this->assertTrue((bool) $item->is_image);

        $collection = MediaCollection::query()->sole();
        $this->assertSame($collection->id, $item->collection_id);
        $this->assertSame('Импорт товаров', $collection->name);
        $this->assertSame(100, (int) $collection->sort);

        $this->assertDatabaseHas('catalog_import_media', [
            'hash' => hash('sha256', 'https://example.com/photos/'.str_repeat('a', 300).'.gif?size=big'),
            'url' => 'https://example.com/photos/'.str_repeat('a', 300).'.gif?size=big',
            'path' => $url,
        ]);
        Http::assertSent(static fn (Request $request): bool => $request->url() === 'https://example.com/photos/'.str_repeat('a', 300).'.gif?size=big');
    }

    public function test_the_disk_of_the_uploads_is_used(): void
    {
        Storage::fake('media');
        config(['atrium.uploads.disk' => 'media']);
        Http::fake(['example.com/*' => Http::response(base64_decode(self::GIF))]);

        $url = $this->images()->one('https://example.com/1.gif');

        $this->assertSame('media', MediaItem::query()->sole()->disk);
        Storage::disk('media')->assertExists(MediaItem::query()->sole()->path);
        $this->assertStringStartsWith('/storage/catalog/', $url);
    }

    public function test_a_second_image_goes_into_the_same_collection(): void
    {
        Http::fake(['example.com/*' => Http::response(base64_decode(self::GIF))]);

        $this->images()->one('https://example.com/1.gif');
        $this->images()->one('https://example.com/2.gif');

        $this->assertSame(1, MediaCollection::query()->count());
        $this->assertSame(2, MediaItem::query()->count());
    }

    public function test_an_image_downloaded_before_is_not_downloaded_again(): void
    {
        DB::table('catalog_import_media')->insert(['hash' => hash('sha256', 'https://example.com/1.gif'), 'url' => 'https://example.com/1.gif', 'path' => '/storage/catalog/old.gif', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame('/storage/catalog/old.gif', $this->images()->one('https://example.com/1.gif'));

        Http::assertNothingSent();
        $this->assertSame(0, MediaItem::query()->count());
    }

    public function test_what_is_not_an_image_is_not_kept(): void
    {
        Http::fake(['example.com/*' => Http::response('<html>Not found</html>')]);

        $this->assertProblem('Not an image (JPEG, PNG, GIF, WebP or SVG): https://example.com/1.gif', 'https://example.com/1.gif');
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, MediaItem::query()->count());
        $this->assertSame(0, DB::table('catalog_import_media')->count());
    }

    public function test_an_svg_is_not_downloaded(): void
    {
        Http::fake(['example.com/*' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"/>')]);

        $this->assertProblem('Not an image (JPEG, PNG, GIF, WebP or SVG): https://example.com/1.svg', 'https://example.com/1.svg');
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function types(): array
    {
        return [
            'jpeg' => ["\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00", 'jpg'],
            'png' => ["\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89", 'png'],
            'webp' => ['RIFF'."\x1a\x00\x00\x00".'WEBPVP8 '."\x0e\x00\x00\x00\x30\x01\x00\x9d\x01\x2a\x01\x00\x01\x00\x02\x00\x34\x25", 'webp'],
        ];
    }

    #[DataProvider('types')]
    public function test_the_extension_comes_from_the_content(string $body, string $extension): void
    {
        Http::fake(['example.com/*' => Http::response($body)]);

        $url = $this->images()->one('https://example.com/image');

        $this->assertStringEndsWith('.'.$extension, $url);
        $this->assertSame($extension, MediaItem::query()->sole()->extension);
    }

    public function test_an_address_inside_the_network_is_not_fetched(): void
    {
        $this->assertProblem('The image is on an address inside the network, which is not fetched: http://inside.test/1.gif', 'http://inside.test/1.gif');
        $this->assertProblem('The image is on an address inside the network, which is not fetched: http://127.0.0.1/1.gif', 'http://127.0.0.1/1.gif');

        Http::assertNothingSent();
    }

    public function test_redirects_are_followed_and_checked_each_time(): void
    {
        Http::fake([
            'https://example.com/1.gif' => Http::response('', 301, ['Location' => '/2.gif']),
            'https://example.com/2.gif' => Http::response('', 302, ['Location' => 'https://cdn.example.com/3.gif']),
            'https://cdn.example.com/3.gif' => Http::response('', 307, ['Location' => '4.gif']),
            'https://cdn.example.com/4.gif' => Http::response(base64_decode(self::GIF)),
        ]);

        $url = $this->images()->one('https://example.com/1.gif');

        $this->assertStringEndsWith('.gif', $url);
        Http::assertSentCount(4);
        $this->assertSame('1.gif', MediaItem::query()->sole()->name);
    }

    public function test_more_than_three_redirects_are_not_followed(): void
    {
        Http::fake([
            'https://example.com/1.gif' => Http::response('', 301, ['Location' => '/2.gif']),
            'https://example.com/2.gif' => Http::response('', 301, ['Location' => '/3.gif']),
            'https://example.com/3.gif' => Http::response('', 301, ['Location' => '/4.gif']),
            'https://example.com/4.gif' => Http::response('', 301, ['Location' => '/5.gif']),
            'https://example.com/5.gif' => Http::response(base64_decode(self::GIF)),
        ]);

        $this->assertProblem('The image could not be downloaded: https://example.com/1.gif', 'https://example.com/1.gif');
        Http::assertSentCount(4);
    }

    public function test_a_redirect_into_the_network_is_not_followed(): void
    {
        Http::fake(['https://example.com/1.gif' => Http::response('', 302, ['Location' => 'http://inside.test/admin'])]);

        $this->assertProblem('The image is on an address inside the network, which is not fetched: http://inside.test/admin', 'https://example.com/1.gif');
        Http::assertSentCount(1);
    }

    public function test_a_server_that_does_not_answer(): void
    {
        Http::fake(['example.com/*' => Http::failedConnection()]);

        $this->assertProblem('The image could not be downloaded: https://example.com/1.gif', 'https://example.com/1.gif');
    }

    public function test_a_missing_image(): void
    {
        Http::fake(['example.com/*' => Http::response(base64_decode(self::GIF), 404)]);

        $this->assertProblem('The image could not be downloaded: https://example.com/1.gif', 'https://example.com/1.gif');
        $this->assertSame(0, MediaItem::query()->count());
    }

    public function test_an_image_up_to_the_limit_is_taken_and_a_larger_one_is_not(): void
    {
        config(['atrium-catalog.import.image_max_size' => 1]);
        $fits = str_pad(base64_decode(self::GIF), 1024, "\0");
        Http::fake([
            'https://example.com/fits.gif' => Http::response($fits),
            'https://example.com/large.gif' => Http::response($fits."\0"),
        ]);

        $this->assertStringEndsWith('.gif', $this->images()->one('https://example.com/fits.gif'));
        $this->assertProblem('The image is larger than 1 KB: https://example.com/large.gif', 'https://example.com/large.gif');
    }

    private function images(bool $check = false): Images
    {
        return new Images(new Hosts(static fn (string $host): array => $host === 'inside.test' ? ['10.0.0.1'] : ['93.184.216.34']), $check);
    }

    private function assertProblem(string $message, string $value): void
    {
        try {
            $this->images()->one($value);
            $this->fail('No problem with '.$value);
        } catch (RowProblem $problem) {
            $this->assertSame($message, $problem->getMessage());
        }
    }
}
