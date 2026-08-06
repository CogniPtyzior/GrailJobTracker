<?php

namespace App\TrackedJob\Domain\ValueObject;

final readonly class TrackedJobNotes
{
    private const MAX_LENGTH = 10000;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Tracked job notes cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Tracked job notes cannot exceed %d characters.', self::MAX_LENGTH));
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
