<?php

declare(strict_types=1);

/*
 * In-memory tracked job repository for application tests.
 * It implements the domain repository port without involving Doctrine or the shared database.
 */

namespace App\Tests\Support\Fake;

use App\Security\Domain\ValueObject\UserId;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;

final class InMemoryTrackedJobRepository implements TrackedJobRepositoryInterface
{
    /** @var array<string, TrackedJob> */
    private array $trackedJobs = [];

    public int $saveCalls = 0;
    public int $removeCalls = 0;

    /** @var array<string, mixed> */
    public array $lastSearch = [];

    /** @var list<string> */
    public array $companySuggestions = [];

    public bool $hasMore = false;

    public function getByIdForOwner(TrackedJobId $id, UserId $ownerId): ?TrackedJob
    {
        $trackedJob = $this->trackedJobs[$id->toRfc4122()] ?? null;

        if ($trackedJob === null || !$trackedJob->ownerId()->equals($ownerId)) {
            return null;
        }

        return $trackedJob;
    }

    public function search(UserId $ownerId, array $filters, int $page, int $pageSize): array
    {
        $this->lastSearch = [
            'ownerId' => $ownerId->toRfc4122(),
            'filters' => $filters,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        return [
            'items' => array_values(array_filter(
                $this->trackedJobs,
                static fn (TrackedJob $trackedJob): bool => $trackedJob->ownerId()->equals($ownerId),
            )),
            'hasMore' => $this->hasMore,
        ];
    }

    public function searchDistinctCompanies(UserId $ownerId, string $query, int $limit = 10): array
    {
        $this->lastSearch = [
            'ownerId' => $ownerId->toRfc4122(),
            'query' => $query,
            'limit' => $limit,
        ];

        return array_slice($this->companySuggestions, 0, $limit);
    }

    public function save(TrackedJob $trackedJob): void
    {
        $this->trackedJobs[$trackedJob->getId()->toRfc4122()] = $trackedJob;
        ++$this->saveCalls;
    }

    public function remove(TrackedJob $trackedJob): void
    {
        unset($this->trackedJobs[$trackedJob->getId()->toRfc4122()]);
        ++$this->removeCalls;
    }

}
