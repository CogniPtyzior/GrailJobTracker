<?php

declare(strict_types=1);

/*
 * In-memory access request repository for application tests.
 * It implements the domain repository port without involving Doctrine or the shared database.
 */

namespace App\Tests\Support\Fake;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

final class InMemoryAccessRequestRepository implements AccessRequestRepositoryInterface
{
    /** @var array<string, AccessRequest> */
    private array $accessRequests = [];

    public int $saveCalls = 0;
    public int $removeCalls = 0;
    public int $flushCalls = 0;

    /** @var array<string, mixed> */
    public array $lastSearch = [];

    /** @param list<AccessRequest> $accessRequests */
    public function __construct(array $accessRequests = [])
    {
        foreach ($accessRequests as $accessRequest) {
            $this->save($accessRequest);
        }
    }

    public function getById(AccessRequestId $id): ?AccessRequest
    {
        return $this->accessRequests[$id->toRfc4122()] ?? null;
    }

    public function search(?string $query, int $page, int $pageSize): array
    {
        $this->lastSearch = [
            'query' => $query,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $items = array_values($this->accessRequests);
        $term = $query === null ? '' : mb_strtolower(trim($query));

        if ($term !== '') {
            $items = array_values(array_filter($items, static function (AccessRequest $accessRequest) use ($term): bool {
                return str_contains(mb_strtolower($accessRequest->getEmail()), $term)
                    || str_contains(mb_strtolower($accessRequest->getCompanyName()), $term)
                    || str_contains(mb_strtolower($accessRequest->reason()->value()), $term);
            }));
        }

        usort($items, static fn (AccessRequest $left, AccessRequest $right): int => $right->getCreatedAt() <=> $left->getCreatedAt());

        return [
            'items' => array_slice($items, max(0, ($page - 1) * $pageSize), $pageSize),
            'total' => count($items),
        ];
    }

    public function save(AccessRequest $accessRequest): void
    {
        $this->accessRequests[$accessRequest->getId()->toRfc4122()] = $accessRequest;
        ++$this->saveCalls;
    }

    public function remove(AccessRequest $accessRequest): void
    {
        unset($this->accessRequests[$accessRequest->getId()->toRfc4122()]);
        ++$this->removeCalls;
    }

    public function flush(): void
    {
        ++$this->flushCalls;
    }
}
