<?php

namespace App\Support;

/**
 * Makes a value safe to put in a CSV that people open in Excel or Sheets:
 * cells starting with = + - @ (or tab/CR) would run as formulas, so they get a
 * leading apostrophe. Use for every value that came from a user.
 */
final class CsvCell
{
    public static function safe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'{$value}" : $value;
    }

    /** @param array<int, mixed> $row */
    public static function row(array $row): array
    {
        return array_map([self::class, 'safe'], $row);
    }
}
