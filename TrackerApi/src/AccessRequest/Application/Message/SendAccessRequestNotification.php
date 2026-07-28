<?php

namespace App\AccessRequest\Application\Message;

/**
 * Async command carrying only a stable id to avoid serializing Doctrine entities.
 */
final readonly class SendAccessRequestNotification
{
    public function __construct(
        public string $accessRequestId,
    ) {
    }
}
