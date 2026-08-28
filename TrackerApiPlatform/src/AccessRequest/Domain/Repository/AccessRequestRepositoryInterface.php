<?php

declare(strict_types=1);

/*
 * Repository port for access request persistence.
 * Application use cases depend on this contract while Doctrine adapters implement it later.
 */

namespace App\AccessRequest\Domain\Repository;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

interface AccessRequestRepositoryInterface
{
    public function getById(AccessRequestId $id): ?AccessRequest;

    /** @return array{items: list<AccessRequest>, total: int} */
    public function search(?string $query, int $page, int $pageSize): array;

    public function save(AccessRequest $accessRequest): void;

    public function remove(AccessRequest $accessRequest): void;
}
