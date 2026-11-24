<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Http;

use Atrium\Catalog\Feed\MarketFeed;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Yandex Market feed of the catalog for the site the visitor came to:
 * /catalog.xml, /de/catalog.xml. It is written as it is read, so a large
 * catalog does not have to fit in memory.
 */
final class MarketFeedController extends Controller
{
    public function __invoke(MarketFeed $feed): StreamedResponse
    {
        $language = app()->getLocale();

        return response()->stream(static function () use ($feed, $language): void {
            $feed->write('php://output', $language);
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
