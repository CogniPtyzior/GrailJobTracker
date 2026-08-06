<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;
use PHPUnit\Framework\TestCase;

final class TrackedJobNotesTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(TrackedJobNotes::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(TrackedJobNotes::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('Strong fit', TrackedJobNotes::fromNullable('  Strong fit  ')?->value());
    }

    public function testFromNullableAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 10000);

        self::assertSame($value, TrackedJobNotes::fromNullable($value)?->value());
    }

    public function testFromNullableRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TrackedJobNotes::fromNullable(str_repeat('a', 10001));
    }
}