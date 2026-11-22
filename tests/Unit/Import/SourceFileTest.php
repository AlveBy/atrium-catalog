<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Tests\Unit\Import;

use Atrium\Catalog\Import\ImportFailed;
use Atrium\Catalog\Import\SourceFile;
use Atrium\Catalog\Tests\TestCase;
use DateInterval;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Cell\DateIntervalCell;
use OpenSpout\Common\Entity\Cell\DateTimeCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Whatever was uploaded becomes one UTF-8 CSV with the source row numbers;
 * the encoding and the delimiter of a CSV file are found by themselves.
 */
final class SourceFileTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/atrium-source-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir.'/*') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function files(): array
    {
        $rows = "Название;Цена\nКуртка;1 299,50\n";

        return [
            'UTF-8, semicolons' => [$rows, 'UTF-8', ';'],
            'UTF-8 with a BOM' => ["\xEF\xBB\xBF".$rows, 'UTF-8', ';'],
            'windows-1251' => [mb_convert_encoding($rows, 'Windows-1251', 'UTF-8'), 'Windows-1251', ';'],
            'commas' => ["Название,Цена\nКуртка,\"1 299,50\"\n", 'UTF-8', ','],
            'tabs' => ["Название\tЦена\nКуртка\t1 299,50\n", 'UTF-8', "\t"],
            'Windows line ends' => ["Название;Цена\r\nКуртка;1 299,50\r\n", 'UTF-8', ';'],
        ];
    }

    #[DataProvider('files')]
    public function test_csv_encoding_and_delimiter_are_recognised(string $content, string $encoding, string $delimiter): void
    {
        $read = $this->normalize($content);

        $this->assertSame(['format' => 'csv', 'encoding' => $encoding, 'delimiter' => $delimiter, 'headers' => ['Название', 'Цена'], 'total' => 1], $read);
        $this->assertSame([['2', 'Куртка', '1 299,50']], $this->rows());
    }

    public function test_a_bom_means_utf8_whatever_follows(): void
    {
        $read = $this->normalize("\xEF\xBB\xBF".mb_convert_encoding("Цена\n1\n", 'Windows-1251', 'UTF-8'));

        $this->assertSame('UTF-8', $read['encoding']);
    }

    public function test_one_column_is_read_with_semicolons(): void
    {
        $read = $this->normalize("Name\nJacket\n");

        $this->assertSame(';', $read['delimiter']);
        $this->assertSame([['2', 'Jacket']], $this->rows());
    }

    public function test_a_letter_cut_by_the_sample_does_not_make_utf8_look_like_windows_1251(): void
    {
        // The 64 KB sample ends inside the two bytes of "ж".
        $line = str_repeat('a', 65530)."ж\n";

        $this->assertSame('UTF-8', $this->normalize("Name\n".$line)['encoding']);
        $this->assertSame('UTF-8', $this->normalize(substr("Name\n".$line, 0, 65536)."\n")['encoding']);
    }

    public function test_a_nul_in_the_first_4_kb_means_not_a_text_file(): void
    {
        $this->assertSame(1, $this->normalize("Name\n".str_repeat('a', 4091)."\0\n")['total']);

        $this->expectException(ImportFailed::class);
        $this->expectExceptionMessage('This is not a CSV or Excel (xlsx) file.');

        $this->normalize("Name\n".str_repeat('a', 4090)."\0\n");
    }

    public function test_empty_rows_are_left_out_and_cells_trimmed_and_numbered_as_in_the_file(): void
    {
        $read = $this->normalize("\n;\n Name ; Price \n\nJacket ;  10\n ; \nCap;\n");

        $this->assertSame(['Name', 'Price'], $read['headers']);
        $this->assertSame(2, $read['total']);
        $this->assertSame([['5', 'Jacket', '10'], ['7', 'Cap', '']], $this->rows());
    }

    public function test_windows_1251_cells_are_converted_and_quotes_kept(): void
    {
        $this->normalize(mb_convert_encoding("Название;Описание\n\n\"Куртка; зимняя\";\"Тёплая \"\"Аляска\"\"\"\n", 'Windows-1251', 'UTF-8'));

        $this->assertSame([['3', 'Куртка; зимняя', 'Тёплая "Аляска"']], $this->rows());
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function broken(): array
    {
        return [
            'empty' => ['', 'The file has no rows.'],
            'only empty rows' => ["\n;;\n", 'The file has no rows.'],
            'a header and no line end' => ['Name;Price', 'The file has no rows.'],
            'a header alone' => ["Name;Price\n\n", 'The file has no rows.'],
            'binary' => ["\xD0\xCF\x11\xE0\0\0\0\0", 'This is not a CSV or Excel (xlsx) file. An old .xls file can be saved as .xlsx or CSV in Excel.'],
            'a damaged zip' => ["PK\x03\x04garbage", 'The Excel file cannot be read: it may be damaged or protected with a password.'],
        ];
    }

    #[DataProvider('broken')]
    public function test_a_file_that_cannot_be_imported_says_why(string $content, string $message): void
    {
        $this->expectException(ImportFailed::class);
        $this->expectExceptionMessage($message);

        $this->normalize($content);
    }

    public function test_excel_first_sheet_with_dates_times_and_flags(): void
    {
        $path = $this->dir.'/source.xlsx';
        $date = (new Style)->setFormat('dd.mm.yyyy');
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([' Name ', 'Price', 'Released', 'In stock', 'Time']));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(new Row([
            Row::fromValues(['Jacket'])->getCellAtIndex(0),
            Row::fromValues([1299.5])->getCellAtIndex(0),
            new DateTimeCell(new DateTimeImmutable('2026-12-31 00:00:00'), $date),
            Row::fromValues([true])->getCellAtIndex(0),
            new DateIntervalCell(new DateInterval('PT10H20M30S'), (new Style)->setFormat('[hh]:mm:ss')),
        ]));
        $writer->addRow(new Row([
            Row::fromValues(['Cap'])->getCellAtIndex(0),
            Row::fromValues([5])->getCellAtIndex(0),
            new DateTimeCell(new DateTimeImmutable('2026-01-02 08:30:00'), $date),
            Row::fromValues([false])->getCellAtIndex(0),
        ]));
        $writer->addNewSheetAndMakeItCurrent();
        $writer->addRow(Row::fromValues(['Other', 'sheet']));
        $writer->close();

        $read = (new SourceFile)->normalize($path, $this->dir.'/out.csv');

        $this->assertSame(['format' => 'xlsx', 'encoding' => 'UTF-8', 'delimiter' => '', 'headers' => ['Name', 'Price', 'Released', 'In stock', 'Time'], 'total' => 2], $read);
        $this->assertSame([
            ['3', 'Jacket', '1299.5', '2026-12-31', '1', '10:20:30'],
            ['4', 'Cap', '5', '2026-01-02 08:30:00', '0'],
        ], $this->rows());
    }

    /**
     * @return array{format:string,encoding:string,delimiter:string,headers:array<int,string>,total:int}
     */
    private function normalize(string $content): array
    {
        file_put_contents($this->dir.'/source', $content);

        return (new SourceFile)->normalize($this->dir.'/source', $this->dir.'/out.csv');
    }

    /**
     * @return array<int,array<int,string>>
     */
    private function rows(): array
    {
        $handle = fopen($this->dir.'/out.csv', 'rb');
        $rows = [];

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
