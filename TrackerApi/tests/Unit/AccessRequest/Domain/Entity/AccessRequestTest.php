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
        self::assertNotNull($accessRequest->getId());
        self::assertNotNull($accessRequest->getCreatedAt());
    }

    public function testOptionalNamesTrimAndConvertBlankStringsToNull(): void
    {
        $accessRequest = new AccessRequest(
            'john@example.com',
            'john@example.com',
            'Acme',
            'Please grant access.',
        );

        $accessRequest->setFirstName('  John  ');
        $accessRequest->setLastName('   ');

        self::assertSame('John', $accessRequest->getFirstName());
        self::assertNull($accessRequest->getLastName());
    }
}