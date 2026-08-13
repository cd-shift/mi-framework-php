<?php

declare(strict_types=1);

namespace Database\Query;

/**
 * Quotes SQL identifiers (table and column names) using the quote style of the
 * active database driver. Shared by PDODriver and the query Builder so
 * identifier escaping and validation live in a single place.
 */
final class Identifier
{
    public static function wrapColumn(string $column, string $protocol): string
    {
        $column = trim($column);

        if (preg_match('/^(.+?)\s+as\s+(.+)$/i', $column, $matches)) {
            return self::wrapSegment($matches[1], $protocol) . ' AS ' . self::wrapSegment($matches[2], $protocol);
        }

        if (str_contains($column, '.')) {
            return implode(
                '.',
                array_map(static fn (string $segment): string => self::wrapSegment($segment, $protocol), explode('.', $column)),
            );
        }

        return self::wrapSegment($column, $protocol);
    }

    public static function wrapSegment(string $segment, string $protocol): string
    {
        $segment = trim($segment);

        if ($segment === '*') {
            return '*';
        }

        // Security: reject anything that is not a plain identifier instead of
        // interpolating it into SQL. Raw expressions must be wrapped in an
        // Expression object before reaching this point.
        if (!preg_match('/^[A-Za-z0-9_$]+$/', $segment)) {
            throw new BuilderException(sprintf(
                'Invalid SQL identifier [%s]. Use Expression for raw SQL instead of a plain string.',
                $segment,
            ));
        }

        [$open, $close] = self::quotePair($protocol);

        return $open . str_replace($close, $close . $close, $segment) . $close;
    }

    private static function quotePair(string $protocol): array
    {
        return match ($protocol) {
            'pgsql' => ['"', '"'],
            'sqlsrv' => ['[', ']'],
            default => ['`', '`'],
        };
    }
}
