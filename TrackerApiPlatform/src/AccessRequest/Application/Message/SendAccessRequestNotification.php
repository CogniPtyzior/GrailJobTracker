<?php

declare(strict_types=1);

/*
 * Application message requesting asynchronous access request notification.
 * It carries only the aggregate identifier so Messenger never serializes domain or Doctrine objects.
 */

namespace App\AccessRequest\Application\Message;

final readonly class SendAccessRequestNotification
{
    public function __construct(public string $accessRequestId)
    {
    }
}
