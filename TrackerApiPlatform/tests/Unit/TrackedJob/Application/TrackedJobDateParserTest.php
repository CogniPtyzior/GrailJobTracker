<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job date parsing.
 * They preserve the frontend-compatible date formats before API inputs are migrated.
 */

use App\TrackedJob\Application\Date\TrackedJobDateParser;

it('parses nullable tracked job dates', function (): void {
    expect(TrackedJobDateParser::parseNullable(null))->toBeNull()
        ->and(TrackedJobDateParser::parseNullable('   '))->toBeNull()
        ->and(TrackedJobDateParser::parseNullable('2026-04-01')?->format('c'))->toBe('2026-04-01T00:00:00+00:00')
        ->and(TrackedJobDateParser::parseNullable('2026-04-01T09:30:00+00:00')?->format('c'))
        ->toBe('2026-04-01T09:30:00+00:00');
});

it('rejects invalid tracked job dates without throwing', function (mixed $value): void {
    expect(TrackedJobDateParser::isValid($value))->toBeFalse()
        ->and(TrackedJobDateParser::parseNullable($value))->toBeNull();
})->with([
    'not a string' => [42],
    'invalid date' => ['2026-02-31'],
    'invalid datetime' => ['2026-04-01T25:00:00+00:00'],
    'missing timezone' => ['2026-04-01T09:30:00'],
]);

it('accepts supported date formats', function (string $value): void {
    expect(TrackedJobDateParser::isValid($value))->toBeTrue();
})->with([
    'date only' => ['2026-04-01'],
    'utc datetime' => ['2026-04-01T09:30:00Z'],
    'offset datetime' => ['2026-04-01T09:30:00+02:00'],
    'fractional seconds' => ['2026-04-01T09:30:00.123456+00:00'],
]);
