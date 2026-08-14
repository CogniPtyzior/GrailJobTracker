<?php

declare(strict_types=1);

/*
 * Unit tests for access request identifiers.
 * They preserve UUID behavior without depending on Symfony UID classes inside the domain.
 */

use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\Shared\Domain\Exception\InvalidDomainData;

it('generates an access request identifier', function (): void {
    expect(AccessRequestId::new()->toRfc4122())
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('restores an access request identifier from string', function (): void {
    $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');

    expect($id->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000003')
        ->and((string) $id)->toBe('018f6d6f-0000-7000-8000-000000000003');
});

it('compares access request identifiers by value', function (): void {
    $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
    $sameId = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
    $otherId = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000004');

    expect($id->equals($sameId))->toBeTrue()
        ->and($id->equals($otherId))->toBeFalse();
});

it('rejects invalid access request identifiers', function (): void {
    AccessRequestId::fromString('not-a-uuid');
})->throws(InvalidDomainData::class, 'Access request id must be a valid UUID.');
