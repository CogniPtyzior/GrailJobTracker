<?php

declare(strict_types=1);

/*
 * Unit tests for required access request text value objects.
 * They preserve legacy trimming and length constraints at the domain boundary.
 */

use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\Exception\InvalidDomainData;

it('normalizes access request company names', function (): void {
    expect(AccessRequestCompanyName::fromString('  Acme  ')->value())->toBe('Acme');
});

it('rejects invalid access request company names', function (?string $value, string $message): void {
    AccessRequestCompanyName::fromString($value ?? '');
})->with([
    'blank' => ['   ', 'Access request company name cannot be blank.'],
    'too long' => [str_repeat('a', 256), 'Access request company name cannot exceed 255 characters.'],
])->throws(InvalidDomainData::class);

it('normalizes access request reasons', function (): void {
    expect(AccessRequestReason::fromString('  I need access to manage jobs.  ')->value())
        ->toBe('I need access to manage jobs.');
});

it('accepts access request reason boundary lengths', function (): void {
    expect(AccessRequestReason::fromString(str_repeat('a', 20))->value())->toBe(str_repeat('a', 20))
        ->and(AccessRequestReason::fromString(str_repeat('b', 5000))->value())->toBe(str_repeat('b', 5000));
});

it('rejects invalid access request reasons', function (string $value): void {
    AccessRequestReason::fromString($value);
})->with([
    'blank' => ['   '],
    'too short' => [str_repeat('a', 19)],
    'too long' => [str_repeat('a', 5001)],
])->throws(InvalidDomainData::class);
