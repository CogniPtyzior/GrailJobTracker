<?php

declare(strict_types=1);

/*
 * Shared date fixtures for domain tests.
 * They keep timeline and status assertions readable without coupling tests to the current clock.
 */

namespace App\Tests\Support\Date;

use DateTimeImmutable;

final readonly class FixedDates
{
    public static function april1(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-01T00:00:00+00:00');
    }

    public static function april5(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-05T00:00:00+00:00');
    }

    public static function april10(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-10T00:00:00+00:00');
    }

    public static function april15(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-15T00:00:00+00:00');
    }

    public static function april20(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-04-20T00:00:00+00:00');
    }
}
