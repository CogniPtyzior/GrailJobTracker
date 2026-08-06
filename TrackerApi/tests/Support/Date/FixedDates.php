<?php

namespace App\Tests\Support\Date;

final class FixedDates
{
    public static function april1(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-04-01T09:00:00+00:00');
    }

    public static function april5(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-04-05T09:00:00+00:00');
    }

    public static function april10(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-04-10T09:00:00+00:00');
    }

    public static function april15(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-04-15T09:00:00+00:00');
    }

    public static function april20(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-04-20T09:00:00+00:00');
    }
}
