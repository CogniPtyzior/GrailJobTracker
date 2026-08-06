<?php

namespace App\TrackedJob\Domain\ValueObject;

final readonly class SubjectiveRelevance
{
    public const MIN = 1;
    public const MAX = 10;

    private function __construct(private int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(sprintf(
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
