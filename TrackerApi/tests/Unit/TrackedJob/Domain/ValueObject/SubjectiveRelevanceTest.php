<?php

namespace App\Tests\Unit\TrackedJob\Domain\ValueObject;

use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubjectiveRelevanceTest extends TestCase
{
    #[DataProvider('validValues')]
    public function testFromIntAcceptsBusinessRange(int $value): void
    {
        self::assertSame($value, SubjectiveRelevance::fromInt($value)->value());
    }

    /** @return iterable<string, array{int}> */
    public static function validValues(): iterable
    {
        yield 'minimum' => [1];
        yield 'middle' => [5];
        yield 'maximum' => [10];
    }

    #[DataProvider('invalidValues')]
    public function testFromIntRejectsValuesOutsideBusinessRange(int $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SubjectiveRelevance::fromInt($value);
    }

    /** @return iterable<string, array{int}> */
    public static function invalidValues(): iterable
    {
        yield 'below minimum' => [0];
        yield 'above maximum' => [11];
    }
}

