<?php

declare(strict_types=1);

/*
 * Application use case that retrieves a tracked job owned by the current user.
 * Ownership is part of the repository query contract to avoid leaking other users' data.
 */

namespace App\TrackedJob\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;

final readonly class GetTrackedJob
{
    public function __construct(private TrackedJobRepositoryInterface $trackedJobRepository)
    {
    }

    public function handle(TrackedJobId $id, User $owner): ?TrackedJob
    {
        return $this->trackedJobRepository->getByIdForOwner($id, $owner->getId());
    }
}
