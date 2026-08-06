<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use PHPUnit\Framework\TestCase;

final class TrackedJobIdTest extends TestCase
{
    public function testNewGeneratesAnIdentifier(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            TrackedJobId::new()->toRfc4122(),
        );
    }

    public function testFromStringRestoresIdentifier(): void
    {
        $id = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');

        self::assertSame('018f6d6f-0000-7000-8000-000000000004', $id->toRfc4122());
        self::assertSame('018f6d6f-0000-7000-8000-000000000004', (string) $id);
    }

    public function testEqualsComparesIdentifierValue(): void
    {
        $id = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');
        $sameId = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');
        $otherId = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000005');

        self::assertTrue($id->equals($sameId));
        self::assertFalse($id->equals($otherId));
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TrackedJobId::fromString('not-a-uuid');
    }
}