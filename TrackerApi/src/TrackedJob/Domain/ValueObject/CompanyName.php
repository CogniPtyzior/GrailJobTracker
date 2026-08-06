<?php

namespace App\TrackedJob\Domain\ValueObject;

final readonly class CompanyName
{
    private const MAX_LENGTH = 255;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Company name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Company name cannot exceed %d characters.', self::MAX_LENGTH));
        }
    }

    public static function fromNullable(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
