<?php

namespace App\TrackedJob\Application;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

/**
 * Application use case that returns company suggestions for the current owner.
 */
final class SuggestTrackedJobCompanies
{
    public function __construct(
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    /**
     * @return list<string>
     */
    public function handle(User $owner, string $query, int $limit = 10): array
    {
        return $this->trackedJobRepository->searchDistinctCompanies($owner, $query, $limit);
    }
}