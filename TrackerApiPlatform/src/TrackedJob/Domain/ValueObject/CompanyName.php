<?php

declare(strict_types=1);

/*
 * Domain value object for optional company names on tracked jobs.
 * It owns trimming, blank handling and length validation before application or API mapping.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class CompanyName
{
    private const int MAX_LENGTH = 255;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Company name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Company name cannot exceed %d characters.', self::MAX_LENGTH));
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
