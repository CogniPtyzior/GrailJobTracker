<?php

namespace App\TrackedJob\Application\UseCase;

use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Input\TrackedJobInput;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that updates an existing tracked job.
 */
final class UpdateTrackedJob
{
    public function __construct(
        private readonly TrackedJobFactory $trackedJobFactory,
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    public function handle(TrackedJob $trackedJob, TrackedJobInput $payload): TrackedJob
    {
        $this->trackedJobFactory->hydrate($trackedJob, $payload);
        $this->trackedJobRepository->save($trackedJob);
        $this->trackedJobRepository->flush();

        return $trackedJob;
    }
}