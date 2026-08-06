<?php

namespace App\Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\PersonName;
use PHPUnit\Framework\TestCase;

final class PersonNameTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(PersonName::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(PersonName::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('John', PersonName::fromNullable('  John  ')?->value());
    }

    public function testFromNullableAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 120);

        self::assertSame($value, PersonName::fromNullable($value)?->value());
    }

    public function testFromNullableRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PersonName::fromNullable(str_repeat('a', 121));
    }
}