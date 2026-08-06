<?php

namespace App\Tests\Unit\AccessRequest\Domain\ValueObject;

use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use PHPUnit\Framework\TestCase;

final class AccessRequestReasonTest extends TestCase
{
    public function testFromStringTrimsValue(): void
    {
        self::assertSame('I need access to manage jobs.', AccessRequestReason::fromString('  I need access to manage jobs.  ')->value());
    }

    public function testFromStringRejectsBlankValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessRequestReason::fromString('   ');
    }

    public function testFromStringRejectsValueBelowMinimumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessRequestReason::fromString(str_repeat('a', 19));
    }

    public function testFromStringAcceptsMinimumLength(): void
    {
        $value = str_repeat('a', 20);

        self::assertSame($value, AccessRequestReason::fromString($value)->value());
    }

    public function testFromStringRejectsValueAboveMaximumLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AccessRequestReason::fromString(str_repeat('a', 5001));
    }
}