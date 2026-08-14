<?php

declare(strict_types=1);

/*
 * Shared domain value object for optional human names.
 * It preserves legacy name normalization while keeping validation inside the domain layer.
 */

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class PersonName
{
    private const int MAX_LENGTH = 120;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Person name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Person name cannot exceed %d characters.', self::MAX_LENGTH));
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

