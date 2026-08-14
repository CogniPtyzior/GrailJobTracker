<?php

declare(strict_types=1);

/*
 * Domain value object for the required company name in a public access request.
 * It mirrors the legacy validation limits while keeping the invariant available outside HTTP.
 */

namespace App\AccessRequest\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class AccessRequestCompanyName
{
    private const int MAX_LENGTH = 255;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Access request company name cannot be blank.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Access request company name cannot exceed %d characters.', self::MAX_LENGTH));
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
