<?php

namespace App\TrackedJob\Application\UseCase;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that deletes an existing tracked job.
 */
final class DeleteTrackedJob
{
    public function __construct(private readonly TrackedJobRepositoryInterface $trackedJobRepository)
    {
    }

    public function handle(TrackedJob $trackedJob): void
    {
        $this->trackedJobRepository->remove($trackedJob);
        $this->trackedJobRepository->flush();
    }
}