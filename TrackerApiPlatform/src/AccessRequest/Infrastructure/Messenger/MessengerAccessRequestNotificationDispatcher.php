<?php

declare(strict_types=1);

/*
 * Messenger adapter for the access request notification dispatcher port.
 * The application triggers the port while this infrastructure adapter decides how the async message is dispatched.
 */

namespace App\AccessRequest\Infrastructure\Messenger;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcherInterface;
use App\AccessRequest\Domain\Entity\AccessRequest;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerAccessRequestNotificationDispatcher implements AccessRequestNotificationDispatcherInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function dispatchCreated(AccessRequest $accessRequest): void
    {
        $this->messageBus->dispatch(
            new SendAccessRequestNotification($accessRequest->getId()->toRfc4122()),
        );
    }
}
