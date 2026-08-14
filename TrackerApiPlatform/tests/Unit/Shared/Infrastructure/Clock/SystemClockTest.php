<?php

declare(strict_types=1);

/*
 * Unit tests for the system clock adapter.
 * They verify infrastructure wiring behavior without making assertions on an exact moving timestamp.
 */

use App\Shared\Application\Clock\ClockInterface;
use App\Shared\Infrastructure\Clock\SystemClock;

it('implements the application clock port', function (): void {
    expect(new SystemClock())->toBeInstanceOf(ClockInterface::class);
});

it('returns an immutable UTC timestamp by default', function (): void {
    $now = (new SystemClock())->now();

    expect($now)
        ->toBeInstanceOf(\DateTimeImmutable::class)
        ->and($now->getTimezone()->getName())
        ->toBe('UTC');
});

it('uses the configured timezone', function (): void {
    $now = (new SystemClock('Europe/Paris'))->now();

    expect($now->getTimezone()->getName())->toBe('Europe/Paris');
});

