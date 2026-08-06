<?php

namespace App\TrackedJob\Domain\ValueObject;

final readonly class OfferUrl
{
    private function __construct(private string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Offer URL cannot be blank.');
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Offer URL must be a valid URL.');
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
