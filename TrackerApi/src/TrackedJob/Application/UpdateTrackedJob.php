<?php

namespace App\TrackedJob\Application;

use App\TrackedJob\Domain\Entity\TrackedJob;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that updates an existing tracked job.
 */
final class UpdateTrackedJob
{
    public function __construct(
        private readonly TrackedJobFactory $trackedJobFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(TrackedJob $trackedJob, array $payload): TrackedJob
    {
        $this->trackedJobFactory->hydrate($trackedJob, $payload);
        $this->entityManager->flush();

        return $trackedJob;
    }
}
