<?php

namespace App\Tests\Unit\AccessRequest\Domain\Entity;

use App\AccessRequest\Domain\Entity\AccessRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class AccessRequestTest extends TestCase
{
    public function testConstructorStoresRequiredFields(): void
    {
        $accessRequest = new AccessRequest(
            'john@example.com',
            'john@example.com',
            'Acme',
            'Please grant access.',
        );

        self::assertSame('john@example.com', $accessRequest->getEmail());
        self::assertSame('john@example.com', $accessRequest->getNormalizedEmail());
        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->getReason());
        self::assertNull($accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
        self::assertNotNull($accessRequest->getId());
        self::assertNotNull($accessRequest->getCreatedAt());
    }

    public function testConstructorTrimsRequiredFields(): void
    {
        $accessRequest = new AccessRequest(
            'john@example.com',
            'john@example.com',
            '  Acme  ',
            '  Please grant access.  ',
        );

        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->getReason());
    }

    public function testSubmitNormalizesRequiredFieldsAndRequesterNames(): void
    {
        $accessRequest = AccessRequest::submit(
            'john@example.com',
            'john@example.com',
            '  Acme  ',
            '  Please grant access.  ',
            '  John  ',
            '   ',
        );

        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->getReason());
        self::assertSame('John', $accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
    }

    public function testSubmitAcceptsNullRequesterNames(): void
    {
        $accessRequest = AccessRequest::submit(
            'john@example.com',
            'john@example.com',
            'Acme',
            'Please grant access.',
            null,
            null,
        );

        self::assertNull($accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
    }

    public function testUpdateRequesterNameTrimsAndConvertsBlankStringsToNull(): void
    {
        $accessRequest = new AccessRequest(
            'john@example.com',
            'john@example.com',
            'Acme',
            'Please grant access.',
        );

        $accessRequest->updateRequesterName('  John  ', '   ');

        self::assertSame('John', $accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
    }

    public function testReconstituteRestoresPersistedStateAndNormalizesNames(): void
    {
        $id = Uuid::fromString('018f6d6f-0000-7000-8000-000000000003');
        $createdAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');

        $accessRequest = AccessRequest::reconstitute(
            $id,
            'john@example.com',
            'john@example.com',
            '  Acme  ',
            '  Please grant access.  ',
            '  John  ',
            '   ',
            $createdAt,
        );

        self::assertSame($id, $accessRequest->getId());
        self::assertSame('john@example.com', $accessRequest->getEmail());
        self::assertSame('john@example.com', $accessRequest->getNormalizedEmail());
        self::assertSame('Acme', $accessRequest->getCompanyName());
        self::assertSame('Please grant access.', $accessRequest->getReason());
        self::assertSame('John', $accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
        self::assertSame($createdAt, $accessRequest->getCreatedAt());
    }
}

