<?php

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Application\Result\SearchTrackedJobsResult;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that searches tracked jobs for the current owner.
 */
final class SearchTrackedJobs
{
    public function __construct(
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(User $owner, array $filters, int $page, int $pageSize): SearchTrackedJobsResult
    {
        $result = $this->trackedJobRepository->search($owner, $filters, $page, $pageSize);

        return new SearchTrackedJobsResult($result['items'], $result['hasMore']);
    }
}
