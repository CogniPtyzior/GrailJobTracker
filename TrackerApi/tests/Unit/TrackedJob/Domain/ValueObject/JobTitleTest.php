<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\JobTitle;
use PHPUnit\Framework\TestCase;

final class JobTitleTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(JobTitle::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(JobTitle::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('Backend Engineer', JobTitle::fromNullable('  Backend Engineer  ')?->value());
    }

    public function testFromNullableAcceptsMaximumLength(): void
    {
        $value = str_repeat('a', 255);

        self::assertSame($value, JobTitle::fromNullable($value)?->value());
    }

    public function testFromNullableRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        JobTitle::fromNullable(str_repeat('a', 256));
    }
}