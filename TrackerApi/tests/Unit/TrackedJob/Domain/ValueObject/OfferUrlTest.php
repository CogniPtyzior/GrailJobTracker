<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\OfferUrl;
use PHPUnit\Framework\TestCase;

final class OfferUrlTest extends TestCase
{
    public function testFromNullableReturnsNullForNull(): void
    {
        self::assertNull(OfferUrl::fromNullable(null));
    }

    public function testFromNullableReturnsNullForBlankValue(): void
    {
        self::assertNull(OfferUrl::fromNullable('   '));
    }

    public function testFromNullableTrimsValue(): void
    {
        self::assertSame('https://example.com/job', OfferUrl::fromNullable('  https://example.com/job  ')?->value());
    }

    public function testFromNullableAcceptsValidUrl(): void
    {
        self::assertSame('https://example.com/job', OfferUrl::fromNullable('https://example.com/job')?->value());
    }

    public function testFromNullableRejectsInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OfferUrl::fromNullable('not-a-url');
    }

    public function testFromNullableDoesNotThrowForEmptyString(): void
    {
        self::assertNull(OfferUrl::fromNullable(''));
    }
}