<?php

declare(strict_types=1);

/*
 * Application use case that updates an existing tracked job.
 * Authorization and loading happen before this use case; it only applies and persists domain changes.
 */

namespace App\TrackedJob\Application\UseCase;

use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class UpdateTrackedJob
{
    public function __construct(
        private TrackedJobCommandApplier $commandApplier,
        private TrackedJobRepositoryInterface $trackedJobRepository,
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
