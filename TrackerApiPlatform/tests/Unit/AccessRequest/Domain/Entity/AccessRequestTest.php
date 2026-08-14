<?php

declare(strict_types=1);

/*
 * Unit tests for the access request aggregate.
 * They verify requester data normalization and reconstitution without API or persistence dependencies.
 */

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

it('stores required access request fields', function (): void {
    $accessRequest = new AccessRequest(
        EmailAddress::fromString('john@example.com'),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('Please grant access.'),
    );

    expect($accessRequest->getEmail())->toBe('john@example.com')
        ->and($accessRequest->getNormalizedEmail())->toBe('john@example.com')
        ->and($accessRequest->getCompanyName())->toBe('Acme')
        ->and($accessRequest->companyName()->value())->toBe('Acme')
        ->and($accessRequest->reason()->value())->toBe('Please grant access.')
        ->and($accessRequest->firstName())->toBeNull()
        ->and($accessRequest->lastName())->toBeNull()
        ->and($accessRequest->getId())->toBeInstanceOf(AccessRequestId::class)
        ->and($accessRequest->getCreatedAt()->getTimezone()->getName())->toBe('UTC');
});

it('submits an access request with optional requester names', function (): void {
    $accessRequest = AccessRequest::submit(
        EmailAddress::fromString('john@example.com'),
        AccessRequestCompanyName::fromString('  Acme  '),
        AccessRequestReason::fromString('  Please grant access.  '),
        PersonName::fromNullable('  John  '),
        PersonName::fromNullable('   '),
    );

    expect($accessRequest->getCompanyName())->toBe('Acme')
        ->and($accessRequest->reason()->value())->toBe('Please grant access.')
        ->and($accessRequest->firstName()?->value())->toBe('John')
        ->and($accessRequest->lastName())->toBeNull();
});

it('updates requester names after submission', function (): void {
    $accessRequest = new AccessRequest(
        EmailAddress::fromString('john@example.com'),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('Please grant access.'),
    );

    $accessRequest->updateRequesterName(PersonName::fromNullable('  Ada  '), PersonName::fromNullable('   '));

    expect($accessRequest->firstName()?->value())->toBe('Ada')
        ->and($accessRequest->lastName())->toBeNull();
});

it('reconstitutes persisted access request state', function (): void {
    $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');

    $accessRequest = AccessRequest::reconstitute(
        $id,
        EmailAddress::fromString('john@example.com'),
        AccessRequestCompanyName::fromString('  Acme  '),
        AccessRequestReason::fromString('  Please grant access.  '),
        PersonName::fromNullable('  John  '),
        PersonName::fromNullable('   '),
        $createdAt,
    );

    expect($accessRequest->getId())->toBe($id)
        ->and($accessRequest->getEmail())->toBe('john@example.com')
        ->and($accessRequest->getNormalizedEmail())->toBe('john@example.com')
        ->and($accessRequest->getCompanyName())->toBe('Acme')
        ->and($accessRequest->reason()->value())->toBe('Please grant access.')
        ->and($accessRequest->firstName()?->value())->toBe('John')
        ->and($accessRequest->lastName())->toBeNull()
        ->and($accessRequest->getCreatedAt())->toBe($createdAt);
});
