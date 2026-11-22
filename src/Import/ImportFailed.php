<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use RuntimeException;

/**
 * A file that cannot be imported at all: not a spreadsheet, empty, unreadable.
 * The message is already translated and is shown to the administrator as it is.
 */
final class ImportFailed extends RuntimeException
{
    /**
     * @param  array<string,string|int>  $replace
     */
    public static function because(string $reason, array $replace = []): self
    {
        return new self((string) __('atrium-catalog::import.failures.'.$reason, $replace));
    }
}
