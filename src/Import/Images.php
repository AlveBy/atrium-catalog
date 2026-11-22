<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Atrium\Admin\Models\MediaCollection;
use Atrium\Admin\Models\MediaItem;
use Atrium\Admin\Support\MediaUrl;
use finfo;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An image cell: a URL to download or a path to a file already on the site.
 *
 * A URL is downloaded into the media library (as an upload in the panel
 * would put it there) and remembered, so importing the same file again does
 * not download it again. Only addresses on the internet are fetched — a file
 * cannot make the server knock on its own network — redirects are followed
 * three times at most and checked the same way, and only JPEG, PNG, GIF and
 * WebP are kept, judged by the content.
 *
 * A path is taken as it is when the file is there: from the site's root
 * ("/upload/catalog/1.jpg") or on the public disk ("catalog/1.jpg").
 *
 * A check downloads nothing: it only looks at whether the URL is one.
 */
final class Images
{
    private const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    private const REDIRECTS = 3;

    public function __construct(private readonly Hosts $hosts, private readonly bool $check) {}

    /**
     * What to keep in the image field.
     */
    public function one(string $value): string
    {
        if (preg_match('#^https?://#i', $value) !== 1) {
            return $this->local($value);
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw RowProblem::because('image_url', ['url' => $value]);
        }

        return $this->check ? $value : $this->download($value);
    }

    private function local(string $value): string
    {
        $path = str_replace('\\', '/', $value);

        if (str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)|^[a-z][a-z0-9+.-]*:#i', $path) === 1) {
            throw RowProblem::because('image_path', ['path' => $value]);
        }

        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
            throw RowProblem::because('image_type', ['path' => $value]);
        }

        $exists = str_starts_with($path, '/') ? is_file(public_path($path)) : Storage::disk('public')->exists($path);

        if (! $exists) {
            throw RowProblem::because('image_missing', ['path' => $value]);
        }

        return $path;
    }

    private function download(string $url): string
    {
        $hash = hash('sha256', $url);
        $known = DB::table('catalog_import_media')->where('hash', $hash)->value('path');

        if (is_string($known)) {
            return $known;
        }

        $body = $this->fetch($url);
        $type = (new finfo(FILEINFO_MIME_TYPE))->buffer($body);
        $extension = self::TYPES[$type] ?? throw RowProblem::because('image_type', ['path' => $url]);

        $disk = config('atrium.uploads.disk', 'public');
        $path = 'catalog/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        Storage::disk($disk)->put($path, $body);
        $stored = MediaUrl::forStored($disk, $path);

        MediaItem::query()->create([
            'collection_id' => MediaCollection::query()->firstOrCreate(['name' => __('atrium-catalog::import.title', [], config('atrium.locale', 'en'))], ['sort' => 100])->id,
            'name' => Str::limit(basename((string) parse_url($url, PHP_URL_PATH)), 250, ''),
            'url' => $stored,
            'disk' => $disk,
            'path' => $path,
            'extension' => $extension,
            'mime_type' => $type,
            'size' => strlen($body),
            'is_image' => true,
        ]);

        DB::table('catalog_import_media')->insert(['hash' => $hash, 'url' => $url, 'path' => $stored, 'created_at' => now(), 'updated_at' => now()]);

        return $stored;
    }

    private function fetch(string $url): string
    {
        $address = $url;

        for ($hop = 0; $hop <= self::REDIRECTS; $hop++) {
            if (! $this->hosts->isPublic((string) parse_url($address, PHP_URL_HOST))) {
                throw RowProblem::because('image_host', ['url' => $address]);
            }

            try {
                $response = Http::timeout(config('atrium-catalog.import.image_timeout'))
                    ->withOptions(['allow_redirects' => false])
                    ->get($address);
            } catch (ConnectionException) {
                throw RowProblem::because('image_download', ['url' => $url]);
            }

            if (! $response->redirect()) {
                break;
            }

            $address = (string) UriResolver::resolve(new Uri($address), new Uri($response->header('Location')));
        }

        if (! $response->successful()) {
            throw RowProblem::because('image_download', ['url' => $url]);
        }

        if (strlen($response->body()) > config('atrium-catalog.import.image_max_size') * 1024) {
            throw RowProblem::because('image_large', ['url' => $url, 'max' => config('atrium-catalog.import.image_max_size')]);
        }

        return $response->body();
    }
}
