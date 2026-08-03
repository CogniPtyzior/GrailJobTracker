<?php

namespace App\Shared\Infrastructure\Validation;

/**
 * Contract implemented by HTTP request DTOs that can be hydrated from a decoded JSON payload.
 */
interface RequestPayload
{
    /**
     * @return list<string>
     */
    public static function expectedFields(): array;

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self;
}
