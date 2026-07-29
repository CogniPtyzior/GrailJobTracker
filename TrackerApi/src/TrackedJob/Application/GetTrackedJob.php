<?php

namespace App\TrackedJob\Application;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Application use case that retrieves a tracked job owned by the current user.
 */
final class GetTrackedJob
{
    public function __construct(
        private readonly TrackedJobRepositoryInterface $trackedJobRepository,
    ) {
    }

    public function handle(string $id, User $owner): ?TrackedJob
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->trackedJobRepository->getByIdForOwner(Uuid::fromString($id), $owner);
    }
}