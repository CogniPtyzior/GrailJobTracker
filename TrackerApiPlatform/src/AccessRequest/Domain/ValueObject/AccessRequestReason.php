<?php

declare(strict_types=1);

/*
 * Domain value object for the access request motivation text.
 * It preserves the legacy minimum and maximum lengths as a domain invariant.
 */

namespace App\AccessRequest\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class AccessRequestReason
{
    private const int MIN_LENGTH = 20;
    private const int MAX_LENGTH = 5000;

    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new InvalidDomainData('Access request reason cannot be blank.');
        }

        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new InvalidDomainData(sprintf('Access request reason must contain at least %d characters.', self::MIN_LENGTH));
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidDomainData(sprintf('Access request reason cannot exceed %d characters.', self::MAX_LENGTH));
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
