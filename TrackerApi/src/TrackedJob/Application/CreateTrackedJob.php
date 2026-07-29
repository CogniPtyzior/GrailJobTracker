<?php

namespace App\TrackedJob\Application;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that creates a tracked job for its owner.
 */
final class CreateTrackedJob
{
    public function __construct(
        private readonly TrackedJobFactory $trackedJobFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(User $owner, array $payload): TrackedJob
    {
        $trackedJob = $this->trackedJobFactory->create($owner, $payload);

        $this->entityManager->persist($trackedJob);
        $this->entityManager->flush();

        return $trackedJob;
    }
}
