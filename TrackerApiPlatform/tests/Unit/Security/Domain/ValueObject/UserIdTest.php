<?php

declare(strict_types=1);

/*
 * Unit tests for user identifiers.
 * They preserve UUID behavior while keeping the value object independent from Symfony UID.
 */

use App\Security\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\InvalidDomainData;

it('generates a UUID identifier', function (): void {
    expect(UserId::new()->toRfc4122())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('restores an identifier from string', function (): void {
    $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');

    expect($id->toRfc4122())
        ->toBe('018f6d6f-0000-7000-8000-000000000001')
        ->and((string) $id)
        ->toBe('018f6d6f-0000-7000-8000-000000000001');
});

it('compares identifier values', function (): void {
    $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
    $sameId = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
    $otherId = UserId::fromString('018f6d6f-0000-7000-8000-000000000002');

    expect($id->equals($sameId))->toBeTrue()
        ->and($id->equals($otherId))->toBeFalse();
});

it('rejects invalid UUID strings', function (): void {
    UserId::fromString('not-a-uuid');
})->throws(InvalidDomainData::class, 'User id must be a valid UUID.');
