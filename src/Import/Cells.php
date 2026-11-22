<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Catalog\Import;

use DateTimeImmutable;

/**
 * How the text of a cell becomes a value, the way people write it in a price
 * list: "1 299,50" is a number, "да" and "Y" are yes, "31.12.2026" is a date.
 * What cannot be read is given back as it was, so the field's own validation
 * says what is wrong with it.
 */
final class Cells
{
    private const YES = ['1', 'y', 'yes', 'true', 'on', '+', 'x', 'да', 'д', 'так', 'ja', 'oui', 'si', 'sí', 'sim', 'tak', 'evet', 'иә', 'ha', '是', 'はい'];

    private const NO = ['0', 'n', 'no', 'false', 'off', '-', 'нет', 'н', 'не', 'ні', 'nein', 'non', 'não', 'nie', 'hayır', 'жоқ', "yo'q", '否', 'いいえ'];

    private const DATES = ['Y-m-d', 'd.m.Y', 'd/m/Y', 'Y.m.d', 'd-m-Y'];

    /**
     * Yes, no, or null when the text is neither.
     */
    public static function bool(string $value): ?bool
    {
        $value = mb_strtolower(trim($value));

        return match (true) {
            in_array($value, self::YES, true) => true,
            in_array($value, self::NO, true) => false,
            default => null,
        };
    }

    /**
     * "1 299,50", "1,299.50" and "1.299,50" are all 1299.50: spaces go, and
     * of a comma and a dot the last one is the decimal point.
     */
    public static function number(string $value): string
    {
        $number = (string) preg_replace('/[\s\x{00A0}\x{202F}\']+/u', '', $value);
        $marks = (string) preg_replace('/[^.,]/', '', $number);

        if ($marks === '') {
            return $number;
        }

        $last = substr($marks, -1);

        if (substr_count($marks, $last) > 1) {
            return str_replace(['.', ','], '', $number);
        }

        [$whole, $fraction] = explode($last, $number);

        return str_replace(['.', ','], '', $whole).'.'.$fraction;
    }

    /**
     * A date as the database keeps it, with the time when $withTime.
     */
    public static function date(string $value, bool $withTime): string
    {
        $value = trim($value);

        foreach (self::DATES as $format) {
            foreach ($withTime ? [$format.' H:i:s', $format.' H:i', $format] : [$format] as $pattern) {
                $date = DateTimeImmutable::createFromFormat('!'.$pattern, $value);

                if ($date !== false && $date->format($pattern) === $value) {
                    return $date->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
                }
            }
        }

        return $value;
    }

    /**
     * Several values in one cell, split by "|" or by lines.
     *
     * @return array<int,string>
     */
    public static function items(string $value): array
    {
        return self::split('/[|\r\n]+/', $value);
    }

    /**
     * Image addresses in one cell: also split by commas, semicolons and spaces,
     * which an address does not contain unencoded.
     *
     * @return array<int,string>
     */
    public static function addresses(string $value): array
    {
        return self::split('/[|,;\s]+/', $value);
    }

    /**
     * For comparing a header or an option with what it may mean: lower case,
     * letters and digits only, so "Цена, руб." and "цена руб" are the same.
     */
    public static function normalize(string $value): string
    {
        return (string) preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($value));
    }

    /**
     * @return array<int,string>
     */
    private static function split(string $pattern, string $value): array
    {
        $parts = explode("\n", (string) preg_replace($pattern, "\n", $value));

        return array_values(array_filter(array_map(trim(...), $parts), static fn (string $part): bool => $part !== ''));
    }
}
