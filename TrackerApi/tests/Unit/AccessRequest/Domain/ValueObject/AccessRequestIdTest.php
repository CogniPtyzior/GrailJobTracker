<?php

namespace App\Tests\Unit\AccessRequest\Domain\ValueObject;

use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use PHPUnit\Framework\TestCase;

final class AccessRequestIdTest extends TestCase
{
    public function testNewGeneratesAnIdentifier(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            AccessRequestId::new()->toRfc4122(),
        );
    }

    public function testFromStringRestoresIdentifier(): void
    {
        $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');

        self::assertSame('018f6d6f-0000-7000-8000-000000000003', $id->toRfc4122());
        self::assertSame('018f6d6f-0000-7000-8000-000000000003', (string) $id);
    }

    public function testEqualsComparesIdentifierValue(): void
    {
        $id = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
        $sameId = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000003');
        $otherId = AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000004');

        self::assertTrue($id->equals($sameId));
        self::assertFalse($id->equals($otherId));
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessRequestId::fromString('not-a-uuid');
    }
}