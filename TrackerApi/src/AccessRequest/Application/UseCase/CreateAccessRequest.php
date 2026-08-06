<?php

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Application\Input\CreateAccessRequestInput;
use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcher;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;

/**
 * Application use case that creates an access request and queues its notification.
 */
final class CreateAccessRequest
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
        private readonly AccessRequestNotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function handle(CreateAccessRequestInput $payload): AccessRequest
    {
        $accessRequest = AccessRequest::submit(
            EmailAddress::fromString($payload->email),
            $payload->companyName,
            $payload->reason,
            $payload->firstName,
            $payload->lastName,
        );

        $this->accessRequestRepository->save($accessRequest);
        $this->accessRequestRepository->flush();
        $this->notificationDispatcher->dispatchCreated($accessRequest);

        return $accessRequest;
    }
}

