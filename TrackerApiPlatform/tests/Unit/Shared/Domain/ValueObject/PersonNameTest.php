<?php

declare(strict_types=1);

/*
 * Unit tests for shared person name normalization.
 * They preserve the legacy behavior before the new API Platform inputs consume this value object.
 */

use App\Shared\Domain\ValueObject\PersonName;

it('returns null for null optional names', function (): void {
    expect(PersonName::fromNullable(null))->toBeNull();
});

it('returns null for blank optional names', function (): void {
    expect(PersonName::fromNullable('   '))->toBeNull();
});

it('trims accepted names', function (): void {
    expect(PersonName::fromNullable('  John  ')?->value())->toBe('John');
});

it('accepts names at the maximum length', function (): void {
    $value = str_repeat('a', 120);

    expect(PersonName::fromNullable($value)?->value())->toBe($value);
});

it('rejects names above the maximum length', function (): void {
    PersonName::fromNullable(str_repeat('a', 121));
})->throws(InvalidArgumentException::class, 'Person name cannot exceed 120 characters.');
