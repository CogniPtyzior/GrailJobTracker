<?php

declare(strict_types=1);

/*
 * Shared domain value object for email identity comparisons.
 * It stores the original value and exposes a normalized value for persistence and equality checks.
 */

namespace App\Shared\Domain\ValueObject;

final readonly class EmailAddress
{
    private function __construct(
        private string $value,
        private string $normalizedValue,
    ) {
    }

    public static function fromString(string $email): self
    {
        return new self($email, self::normalize($email));
    }

    public static function reconstitute(string $email, string $normalizedEmail): self
    {
        return new self($email, self::normalize($normalizedEmail));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function normalizedValue(): string
    {
        return $this->normalizedValue;
    }

    public function equals(self $other): bool
    {
        return $this->normalizedValue === $other->normalizedValue;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
