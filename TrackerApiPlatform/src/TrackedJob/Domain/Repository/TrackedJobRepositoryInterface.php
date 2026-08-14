<?php

declare(strict_types=1);

/*
 * Domain repository port for tracked jobs.
 * Application use cases depend on this abstraction while persistence adapters provide the implementation.
 */

namespace App\TrackedJob\Domain\Repository;

use App\Security\Domain\ValueObject\UserId;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;

interface TrackedJobRepositoryInterface
{
    public function getByIdForOwner(TrackedJobId $id, UserId $ownerId): ?TrackedJob;

    /**
     * @param array<string, mixed> $filters
     * @return array{items: list<TrackedJob>, hasMore: bool}
     */
    public function search(UserId $ownerId, array $filters, int $page, int $pageSize): array;

    /** @return list<string> */
    public function searchDistinctCompanies(UserId $ownerId, string $query, int $limit = 10): array;

    public function save(TrackedJob $trackedJob): void;

    public function remove(TrackedJob $trackedJob): void;

    public function flush(): void;
}
