<?php

declare(strict_types=1);

/*
 * Domain value object for the optional subjective relevance score.
 * It keeps the one-to-ten scoring invariant available outside the HTTP layer.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class SubjectiveRelevance
{
    public const int MIN = 1;
    public const int MAX = 10;

    private function __construct(private int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new InvalidDomainData(sprintf(
                'Subjective relevance must be between %d and %d.',
                self::MIN,
                self::MAX,
            ));
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
