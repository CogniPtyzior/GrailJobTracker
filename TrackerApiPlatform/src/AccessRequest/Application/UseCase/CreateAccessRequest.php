<?php

declare(strict_types=1);

/*
 * Application use case that creates a public access request and triggers notification dispatch.
 * Persistence and notification details remain behind ports so API and infrastructure can evolve independently.
 */

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Application\Input\CreateAccessRequestInput;
use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcherInterface;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;

final readonly class CreateAccessRequest
{
    public function __construct(
        private AccessRequestRepositoryInterface $accessRequestRepository,
        private AccessRequestNotificationDispatcherInterface $notificationDispatcher,
    ) {
    }

    public function handle(CreateAccessRequestInput $input): AccessRequest
    {
        $accessRequest = AccessRequest::submit(
            EmailAddress::fromString($input->email),
            $input->companyName,
            $input->reason,
            $input->firstName,
            $input->lastName,
        );

        $this->accessRequestRepository->save($accessRequest);
        $this->accessRequestRepository->flush();
        $this->notificationDispatcher->dispatchCreated($accessRequest);

        return $accessRequest;
    }
}
