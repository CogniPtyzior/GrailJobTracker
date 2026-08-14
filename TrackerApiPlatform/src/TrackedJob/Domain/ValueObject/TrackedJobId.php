<?php

declare(strict_types=1);

/*
 * Domain value object for tracked job identifiers.
 * It mirrors the shared database UUID format while keeping the domain independent from framework UID classes.
 */

namespace App\TrackedJob\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidDomainData;

final readonly class TrackedJobId
{
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private function __construct(private string $value)
    {
        if (preg_match(self::UUID_PATTERN, $value) !== 1) {
            throw new InvalidDomainData('Tracked job id must be a valid UUID.');
        }
    }

    public static function new(): self
    {
        return self::fromString(self::generateUuidV4());
    }

    public static function fromString(string $id): self
    {
        return new self(mb_strtolower(trim($id)));
    }

    public function toRfc4122(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );
    }
}
