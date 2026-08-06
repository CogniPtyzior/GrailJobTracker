<?php

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;

/**
 * Application use case that retrieves a tracked job owned by the current user.
 */
final class GetTrackedJob
{
    public function __construct(
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    public function handle(TrackedJobId $id, User $owner): ?TrackedJob
    {
        return $this->trackedJobRepository->getByIdForOwner($id, $owner);
    }
}
