<?php

declare(strict_types=1);

/*
 * Application use case that searches tracked jobs for the current owner.
 * Filtering semantics are delegated to the repository port and will be implemented by persistence adapters.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Application\Result\SearchTrackedJobsResult;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class SearchTrackedJobs
{
    public function __construct(private TrackedJobRepositoryInterface $trackedJobRepository)
    {
    }

    /** @param array<string, mixed> $filters */
    public function handle(User $owner, array $filters, int $page, int $pageSize): SearchTrackedJobsResult
    {
        $result = $this->trackedJobRepository->search($owner->getId(), $filters, $page, $pageSize);

        return new SearchTrackedJobsResult($result['items'], $result['hasMore']);
    }
}
