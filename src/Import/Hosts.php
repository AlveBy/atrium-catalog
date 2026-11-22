<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use Closure;

/**
 * Whether a host is on the internet: every address it resolves to is public,
 * none private, loopback, link-local or reserved. A file of products must not
 * make the server fetch its own admin, a database or a cloud's metadata.
 */
final class Hosts
{
    /** @var Closure(string):(array<int,string>|false) */
    private readonly Closure $lookup;

    /**
     * @param  (Closure(string):(array<int,string>|false))|null  $lookup  the addresses of a name; the system's resolver by default
     */
    public function __construct(?Closure $lookup = null)
    {
        $this->lookup = $lookup ?? gethostbynamel(...);
    }

    public function isPublic(string $host): bool
    {
        $host = trim($host, '[]');
        $addresses = filter_var($host, FILTER_VALIDATE_IP) === false ? ($this->lookup)($host) : [$host];

        if ($addresses === false || $addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
