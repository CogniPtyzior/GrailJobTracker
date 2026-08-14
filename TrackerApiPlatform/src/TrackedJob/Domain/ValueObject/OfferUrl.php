<?php

declare(strict_types=1);

/*
 * Domain value object for optional offer URLs.
 * It validates URL syntax independently from API Platform input validation.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class OfferUrl
{
    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Offer URL cannot be blank.');
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidDomainData('Offer URL must be a valid URL.');
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
