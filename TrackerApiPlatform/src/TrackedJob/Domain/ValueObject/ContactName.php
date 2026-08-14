<?php

declare(strict_types=1);

/*
 * Domain value object for optional HR and business contact names.
 * Contact names are normalized in the domain before they reach API outputs or persistence records.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class ContactName
{
    private const int MAX_LENGTH = 255;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Contact name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Contact name cannot exceed %d characters.', self::MAX_LENGTH));
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
