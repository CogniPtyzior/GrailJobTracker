<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validation;

/**
 * Raised when raw JSON data cannot hydrate a typed request DTO.
 */
final class RequestPayloadHydrationException extends \RuntimeException
{
    public static function invalidPayload(): self
    {
        return new self('Invalid request payload.');
    }
}
