<?php

declare(strict_types=1);

/*
 * Infrastructure clock adapter backed by the system time.
 * Symfony injects it behind the application clock port for production code.
 */

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Application\Clock\ClockInterface;
use DateTimeImmutable;
use DateTimeZone;

final readonly class SystemClock implements ClockInterface
{
    public function __construct(private string $timezone = 'UTC')
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone($this->timezone));
    }
}
