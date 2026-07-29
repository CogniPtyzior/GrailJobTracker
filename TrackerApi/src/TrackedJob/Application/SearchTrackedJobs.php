<?php

namespace App\TrackedJob\Application;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
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
     * @return array{items: list<TrackedJob>, hasMore: bool}
     */
    public function handle(User $owner, array $filters, int $page, int $pageSize): array
    {
        return $this->trackedJobRepository->search($owner, $filters, $page, $pageSize);
    }
}