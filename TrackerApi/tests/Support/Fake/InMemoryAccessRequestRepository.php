<?php

namespace App\Tests\Support\Fake;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryAccessRequestRepository implements AccessRequestRepositoryInterface
{
    /** @var array<string, AccessRequest> */
    private array $accessRequestsById = [];

    /** @param list<AccessRequest> $accessRequests */
    public function __construct(array $accessRequests = [])
    {
        foreach ($accessRequests as $accessRequest) {
            $this->save($accessRequest);
        }
    }

    public function getById(Uuid $id): ?AccessRequest
    {
        return $this->accessRequestsById[$id->toRfc4122()] ?? null;
    }

    public function search(?string $query, int $page, int $pageSize): array
    {
        $items = array_values($this->accessRequestsById);

        if ($query !== null && $query !== '') {
            $term = mb_strtolower(trim($query));
            $items = array_values(array_filter($items, static function (AccessRequest $accessRequest) use ($term): bool {
                return str_contains(mb_strtolower($accessRequest->getEmail()), $term)
                    || str_contains(mb_strtolower($accessRequest->getCompanyName()), $term)
                    || str_contains(mb_strtolower($accessRequest->getReason()), $term);
            }));
        }

        usort($items, static fn (AccessRequest $left, AccessRequest $right): int => $right->getCreatedAt() <=> $left->getCreatedAt());

        $offset = max(0, ($page - 1) * $pageSize);

        return [
            'items' => array_slice($items, $offset, $pageSize),
            'total' => count($items),
        ];
    }

    public function save(AccessRequest $accessRequest): void
    {
        $this->accessRequestsById[$accessRequest->getId()->toRfc4122()] = $accessRequest;
    }

    public function remove(AccessRequest $accessRequest): void
    {
        unset($this->accessRequestsById[$accessRequest->getId()->toRfc4122()]);
    }

    public function flush(): void
    {
    }

    public function exists(AccessRequest $accessRequest): bool
    {
        return isset($this->accessRequestsById[$accessRequest->getId()->toRfc4122()]);
    }
}