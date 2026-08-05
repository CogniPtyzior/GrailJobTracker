<?php

namespace App\Tests\Unit\AccessRequest\Domain\Entity;

use App\AccessRequest\Domain\Entity\AccessRequest;
use PHPUnit\Framework\TestCase;

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
}