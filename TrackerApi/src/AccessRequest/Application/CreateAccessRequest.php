<?php

namespace App\AccessRequest\Application;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\Security\Application\EmailNormalizer;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that creates an access request and queues its notification.
 */
final class CreateAccessRequest
{
    public function __construct(
        private readonly EmailNormalizer $emailNormalizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessRequestNotificationDispatcher $notificationDispatcher,
    ) {
    }

    public function handle(CreateAccessRequestInput $payload): AccessRequest
    {
        $accessRequest = new AccessRequest(
            $payload->email,
            $this->emailNormalizer->normalize($payload->email),
            trim($payload->companyName),
            trim($payload->reason),
        );

        $accessRequest->setFirstName($payload->firstName);
        $accessRequest->setLastName($payload->lastName);

        $this->entityManager->persist($accessRequest);
        $this->entityManager->flush();
        $this->notificationDispatcher->dispatchCreated($accessRequest);

        return $accessRequest;
    }
}
