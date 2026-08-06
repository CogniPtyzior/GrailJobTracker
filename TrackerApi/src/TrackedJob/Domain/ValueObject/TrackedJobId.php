<?php

namespace App\TrackedJob\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final readonly class TrackedJobId
{
    private function __construct(private Uuid $value)
    {
    }

    public static function new(): self
    {
        return new self(new UuidV7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public static function fromUuid(Uuid $id): self
    {
        return new self($id);
    }

    public function toUuid(): Uuid
    {
        return $this->value;
    }

    public function toRfc4122(): string
    {
        return $this->value->toRfc4122();
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->toRfc4122();
    }
}
