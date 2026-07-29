<?php

namespace App\TrackedJob\Application;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Infrastructure\Doctrine\TrackedJobRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that deletes a tracked job owned by the current user.
 */
final class DeleteTrackedJob
{
    public function __construct(
        private readonly TrackedJobRepository $trackedJobRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(TrackedJob $trackedJob): void
    {
        $this->trackedJobRepository->delete($trackedJob);
        $this->entityManager->flush();
    }
}
