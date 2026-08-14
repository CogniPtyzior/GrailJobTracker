<?php

declare(strict_types=1);

/*
 * Domain value object for optional tracked job notes.
 * The maximum length follows the legacy business rule while exposing a compact immutable value.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class TrackedJobNotes
{
    private const int MAX_LENGTH = 10000;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Tracked job notes cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Tracked job notes cannot exceed %d characters.', self::MAX_LENGTH));
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
