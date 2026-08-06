<?php

namespace App\Tests\Unit\AccessRequest\Domain\Entity;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use PHPUnit\Framework\TestCase;

final class AccessRequestTest extends TestCase
{
    public function testConstructorStoresRequiredFields(): void
    {
        $accessRequest = new AccessRequest(
            EmailAddress::fromString('john@example.com'),
            'Acme',
            AccessRequestReason::fromString('Please grant access.'),
        );

        self::assertSame('john@example.com', $accessRequest->getEmail());
        self::assertSame('john@example.com', $accessRequest->getNormalizedEmail());
        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->reason()->value());
        self::assertNull($accessRequest->firstName()?->value());
        self::assertNull($accessRequest->lastName()?->value());
        self::assertNotNull($accessRequest->getId());
        self::assertNotNull($accessRequest->getCreatedAt());
    }

    public function testConstructorTrimsRequiredFields(): void
    {
        $accessRequest = new AccessRequest(
            EmailAddress::fromString('john@example.com'),
            '  Acme  ',
            AccessRequestReason::fromString('  Please grant access.  '),
        );

        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->reason()->value());
    }

    public function testSubmitNormalizesRequiredFieldsAndRequesterNames(): void
    {
        $accessRequest = AccessRequest::submit(
            EmailAddress::fromString('john@example.com'),
            '  Acme  ',
            AccessRequestReason::fromString('  Please grant access.  '),
            PersonName::fromNullable('  John  '),
            PersonName::fromNullable('   '),
        );

        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->reason()->value());
        self::assertSame('John', $accessRequest->firstName()?->value());
        self::assertNull($accessRequest->lastName()?->value());
    }

    public function testSubmitAcceptsNullRequesterNames(): void
    {
        $accessRequest = AccessRequest::submit(
            EmailAddress::fromString('john@example.com'),
            'Acme',
            AccessRequestReason::fromString('Please grant access.'),
            null,
            null,
        );

        self::assertNull($accessRequest->firstName()?->value());
        self::assertNull($accessRequest->lastName()?->value());
    }

    public function testUpdateRequesterNameTrimsAndConvertsBlankStringsToNull(): void
    {
        $accessRequest = new AccessRequest(
            EmailAddress::fromString('john@example.com'),
            'Acme',
            AccessRequestReason::fromString('Please grant access.'),
        );

        $accessRequest->updateRequesterName(PersonName::fromNullable('  John  '), PersonName::fromNullable('   '));

        self::assertSame('John', $accessRequest->firstName()?->value());
        self::assertNull($accessRequest->lastName()?->value());
    }

    public function testReconstituteRestoresPersistedStateAndNormalizesNames(): void
    {
        $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
        $createdAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');

        $accessRequest = AccessRequest::reconstitute(
            $id,
            EmailAddress::fromString('john@example.com'),
            '  Acme  ',
            AccessRequestReason::fromString('  Please grant access.  '),
            PersonName::fromNullable('  John  '),
            PersonName::fromNullable('   '),
            $createdAt,
        );

        self::assertSame($id, $accessRequest->getId());
        self::assertSame('john@example.com', $accessRequest->getEmail());
        self::assertSame('john@example.com', $accessRequest->getNormalizedEmail());
        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->reason()->value());
        self::assertSame('John', $accessRequest->firstName()?->value());
        self::assertNull($accessRequest->lastName()?->value());
        self::assertSame($createdAt, $accessRequest->getCreatedAt());
    }
}

