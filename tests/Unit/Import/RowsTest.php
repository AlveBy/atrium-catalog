<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Unit\Import;

use Atrium\Catalog\Import\Rows;
use PHPUnit\Framework\TestCase;

/**
 * The normalized file is read a piece at a time from a byte offset.
 */
final class RowsTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = (string) tempnam(sys_get_temp_dir(), 'atrium-rows');
        file_put_contents($this->file, "2,Jacket,10\n5,\"Cap, warm\",\"\"\n9,Scarf,3\n");
    }

    protected function tearDown(): void
    {
        unlink($this->file);

        parent::tearDown();
    }

    public function test_reads_from_an_offset_up_to_a_limit_and_says_where_each_row_ends(): void
    {
        $first = Rows::read($this->file, 0, 2);

        $this->assertSame([
            ['number' => 2, 'cells' => ['Jacket', '10'], 'end' => 12],
            ['number' => 5, 'cells' => ['Cap, warm', ''], 'end' => 29],
        ], $first);

        $this->assertSame([['number' => 9, 'cells' => ['Scarf', '3'], 'end' => 39]], Rows::read($this->file, 29, 2));
        $this->assertSame([], Rows::read($this->file, 39, 2));
    }

    public function test_finished_once_the_offset_reaches_the_end(): void
    {
        $this->assertFalse(Rows::finished($this->file, 0));
        $this->assertFalse(Rows::finished($this->file, 38));
        $this->assertTrue(Rows::finished($this->file, 39));
        $this->assertTrue(Rows::finished($this->file, 40));
    }

    public function test_preview_is_the_cells_of_the_first_rows(): void
    {
        $this->assertSame([['Jacket', '10']], Rows::preview($this->file, 1));
        $this->assertSame([['Jacket', '10'], ['Cap, warm', ''], ['Scarf', '3']], Rows::preview($this->file, 5));
    }
}
