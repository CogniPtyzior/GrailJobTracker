<?php

namespace App\AccessRequest\Infrastructure\Messenger\MessageHandler;

use App\AccessRequest\Infrastructure\Notification\AccessRequestNotificationSender;
use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

/**
 * Worker-side handler that reloads the access request before sending the email.
 */
#[AsMessageHandler]
final class SendAccessRequestNotificationHandler
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
        private readonly AccessRequestNotificationSender $notificationSender,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendAccessRequestNotification $message): void
    {
        try {
            $accessRequestId = AccessRequestId::fromString($message->accessRequestId);
        } catch (\InvalidArgumentException) {
            $this->logger->warning('Access request notification skipped for invalid id.', [
                'accessRequestId' => $message->accessRequestId,
            ]);

            return;
        }

        $accessRequest = $this->accessRequestRepository->getById($accessRequestId);

        if ($accessRequest === null) {
            $this->logger->warning('Access request notification skipped because the request no longer exists.', [
                'accessRequestId' => $message->accessRequestId,
            ]);

            return;
        }

        $this->notificationSender->sendCreatedNotificationOrFail($accessRequest);
    }
}
