<?php

namespace App\AccessRequest\Domain\ValueObject;

final readonly class AccessRequestReason
{
    private const MIN_LENGTH = 20;
    private const MAX_LENGTH = 5000;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Access request reason cannot be blank.');
        }

        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Access request reason must contain at least %d characters.', self::MIN_LENGTH));
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Access request reason cannot exceed %d characters.', self::MAX_LENGTH));
        }
    }

    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    public function value(): string
    {
        return $this->value;
    }
}