<?php

declare(strict_types=1);

/*
 * Application use case that deletes an existing tracked job.
 * It depends only on the repository port so persistence deletion details stay in infrastructure.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class DeleteTrackedJob
{
    public function __construct(
        private TrackedJobRepositoryInterface $trackedJobRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function handle(TrackedJob $trackedJob): void
    {
        $this->transactionManager->transactional(function () use ($trackedJob): void {
            $this->trackedJobRepository->remove($trackedJob);
        });
    }
}
