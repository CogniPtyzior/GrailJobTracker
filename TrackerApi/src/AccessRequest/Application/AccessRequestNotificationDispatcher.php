<?php

namespace App\AccessRequest\Application;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Domain\Entity\AccessRequest;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Application boundary that hides Messenger from the HTTP layer.
 */
final class AccessRequestNotificationDispatcher
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchCreated(AccessRequest $accessRequest): void
    {
        $this->messageBus->dispatch(
            new SendAccessRequestNotification($accessRequest->getId()->toRfc4122()),
        );
    }
}
