<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\CompanyName;
use PHPUnit\Framework\TestCase;

final class CompanyNameTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(CompanyName::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(CompanyName::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('Acme', CompanyName::fromNullable('  Acme  ')?->value());
    }

    public function testFromNullableAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 255);

        self::assertSame($value, CompanyName::fromNullable($value)?->value());
    }

    public function testFromNullableRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CompanyName::fromNullable(str_repeat('a', 256));
    }
}