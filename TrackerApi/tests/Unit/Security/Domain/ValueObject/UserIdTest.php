<?php

namespace App\Tests\Unit\Security\Domain\ValueObject;

use App\Security\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    public function testNewGeneratesAnIdentifier(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            UserId::new()->toRfc4122(),
        );
    }

    public function testFromStringRestoresIdentifier(): void
    {
        $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');

        self::assertSame('018f6d6f-0000-7000-8000-000000000001', $id->toRfc4122());
        self::assertSame('018f6d6f-0000-7000-8000-000000000001', (string) $id);
    }

    public function testEqualsComparesIdentifierValue(): void
    {
        $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
        $sameId = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
        $otherId = UserId::fromString('018f6d6f-0000-7000-8000-000000000002');

        self::assertTrue($id->equals($sameId));
        self::assertFalse($id->equals($otherId));
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UserId::fromString('not-a-uuid');
    }
}