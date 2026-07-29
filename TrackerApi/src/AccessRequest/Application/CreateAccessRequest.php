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

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): AccessRequest
    {
        $accessRequest = new AccessRequest(
            (string) $payload['email'],
            $this->emailNormalizer->normalize((string) $payload['email']),
            trim((string) $payload['companyName']),
            trim((string) $payload['reason']),
        );

        $accessRequest->setFirstName($payload['firstName'] ?? null);
        $accessRequest->setLastName($payload['lastName'] ?? null);

        $this->entityManager->persist($accessRequest);
        $this->entityManager->flush();
        $this->notificationDispatcher->dispatchCreated($accessRequest);

        return $accessRequest;
    }
}
