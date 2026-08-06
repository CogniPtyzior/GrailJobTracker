<?php

namespace App\TrackedJob\Application\UseCase;

use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that updates an existing tracked job.
 */
final class UpdateTrackedJob
{
    public function __construct(
        private readonly TrackedJobCommandApplier $commandApplier,
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    public function handle(TrackedJob $trackedJob, TrackedJobCommand $command): TrackedJob
    {
        $this->commandApplier->apply($trackedJob, $command);
        $this->trackedJobRepository->save($trackedJob);
        $this->trackedJobRepository->flush();

        return $trackedJob;
    }
}
