<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Unit\Import;

use Atrium\Catalog\Import\Hosts;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Images are fetched from the internet only: never from the server's own
 * network, whatever name points there.
 */
final class HostsTest extends TestCase
{
    /**
     * @return array<string,array{string,bool}>
     */
    public static function addresses(): array
    {
        return [
            'public IPv4' => ['93.184.216.34', true],
            'public IPv6 in brackets' => ['[2606:2800:220:1:248:1893:25c8:1946]', true],
            'loopback' => ['127.0.0.1', false],
            'private' => ['10.0.0.5', false],
            'private 192.168' => ['192.168.1.1', false],
            'link-local, cloud metadata' => ['169.254.169.254', false],
            'unspecified' => ['0.0.0.0', false],
            'IPv6 loopback' => ['[::1]', false],
            'IPv6 private' => ['[fd00::1]', false],
        ];
    }

    #[DataProvider('addresses')]
    public function test_addresses_are_judged_as_they_are(string $host, bool $public): void
    {
        $hosts = new Hosts(static fn (): never => throw new \LogicException('An address is not looked up.'));

        $this->assertSame($public, $hosts->isPublic($host));
    }

    /**
     * @return array<string,array{array<int,string>|false,bool}>
     */
    public static function lookups(): array
    {
        return [
            'public' => [['93.184.216.34'], true],
            'all public' => [['93.184.216.34', '1.1.1.1'], true],
            'one of them private' => [['93.184.216.34', '10.0.0.1'], false],
            'private first' => [['127.0.0.1', '93.184.216.34'], false],
            'nothing found' => [false, false],
            'no addresses' => [[], false],
        ];
    }

    /**
     * @param  array<int,string>|false  $addresses
     */
    #[DataProvider('lookups')]
    public function test_a_name_is_public_when_all_its_addresses_are(array|false $addresses, bool $public): void
    {
        $asked = [];
        $hosts = new Hosts(static function (string $host) use (&$asked, $addresses): array|false {
            $asked[] = $host;

            return $addresses;
        });

        $this->assertSame($public, $hosts->isPublic('images.example.com'));
        $this->assertSame(['images.example.com'], $asked);
    }

    public function test_the_system_resolver_by_default(): void
    {
        $this->assertFalse((new Hosts)->isPublic('localhost'));
        $this->assertFalse((new Hosts)->isPublic('no-such-host.invalid'));
    }
}
