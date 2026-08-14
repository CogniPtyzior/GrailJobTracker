<?php

declare(strict_types=1);

/*
 * Application use case that returns company suggestions for the current owner.
 * It keeps the API autocomplete operation independent from the persistence implementation.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;

final readonly class SuggestTrackedJobCompanies
{
    public function __construct(private TrackedJobRepositoryInterface $trackedJobRepository)
    {
    }

    /** @return list<string> */
    public function handle(User $owner, string $query, int $limit = 10): array
    {
        return $this->trackedJobRepository->searchDistinctCompanies($owner->getId(), $query, $limit);
    }
}
