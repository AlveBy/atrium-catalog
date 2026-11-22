<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use RuntimeException;

/**
 * One row that cannot be imported, with the reason, already translated. The
 * import goes on with the next row; the reason goes into the report.
 */
final class RowProblem extends RuntimeException
{
    /**
     * @param  array<string,string|int>  $replace
     */
    public static function because(string $reason, array $replace = []): self
    {
        return new self((string) __('atrium-catalog::import.problems.'.$reason, $replace));
    }
}
