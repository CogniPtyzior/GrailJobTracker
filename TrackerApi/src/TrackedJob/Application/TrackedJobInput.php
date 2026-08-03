<?php

namespace App\TrackedJob\Application;

/**
 * Application input object carrying the tracked job fields accepted by create/update use cases.
 */
final readonly class TrackedJobInput
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private array $payload)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
