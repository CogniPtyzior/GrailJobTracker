<?php

declare(strict_types=1);

/*
 * Worker-side handler for access request notification messages.
 * It reloads the aggregate by id and acknowledges stale or malformed messages without retrying forever.
 */

namespace App\AccessRequest\Infrastructure\Messenger\MessageHandler;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Infrastructure\Notification\AccessRequestNotificationSender;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendAccessRequestNotificationHandler
{
    public function __construct(
        private AccessRequestRepositoryInterface $accessRequestRepository,
        private AccessRequestNotificationSender $notificationSender,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendAccessRequestNotification $message): void
    {
        try {
            $accessRequestId = AccessRequestId::fromString($message->accessRequestId);
        } catch (InvalidArgumentException) {
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
