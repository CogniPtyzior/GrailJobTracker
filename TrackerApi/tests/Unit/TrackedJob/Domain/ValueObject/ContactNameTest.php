<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\ContactName;
use PHPUnit\Framework\TestCase;

final class ContactNameTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(ContactName::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(ContactName::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('Jane HR', ContactName::fromNullable('  Jane HR  ')?->value());
    }

    public function testFromNullableAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 255);

        self::assertSame($value, ContactName::fromNullable($value)?->value());
    }

    public function testFromNullableRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContactName::fromNullable(str_repeat('a', 256));
    }
}