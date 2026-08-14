<?php

declare(strict_types=1);

/*
 * Application port for time access.
 * Use cases depend on this abstraction so tests can control time without coupling to infrastructure.
 */

namespace App\Shared\Application\Clock;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;
}
