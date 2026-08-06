<?php

namespace App\AccessRequest\Infrastructure\Messenger;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcherInterface;
use App\AccessRequest\Domain\Entity\AccessRequest;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAccessRequestNotificationDispatcher implements AccessRequestNotificationDispatcherInterface
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