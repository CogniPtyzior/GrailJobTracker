<?php

declare(strict_types=1);

/*
 * Application use case that creates a tracked job for its owner.
 * It coordinates aggregate creation, command application and repository persistence through ports.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class CreateTrackedJob
{
    public function __construct(
        private TrackedJobFactory $trackedJobFactory,
        private TrackedJobCommandApplier $commandApplier,
        private TrackedJobRepositoryInterface $trackedJobRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function handle(User $owner, TrackedJobCommand $command): TrackedJob
    {
        return $this->transactionManager->transactional(function () use ($owner, $command): TrackedJob {
            $trackedJob = $this->trackedJobFactory->create($owner);
            $this->commandApplier->apply($trackedJob, $command);

            $this->trackedJobRepository->save($trackedJob);

            return $trackedJob;
        });
    }
}
