<?php

namespace App\Shared\Domain\ValueObject;

final readonly class PersonName
{
    private const MAX_LENGTH = 120;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Person name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Person name cannot exceed %d characters.', self::MAX_LENGTH));
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
