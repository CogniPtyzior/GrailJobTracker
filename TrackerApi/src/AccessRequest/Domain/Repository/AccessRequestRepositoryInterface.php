<?php

namespace App\AccessRequest\Domain\Repository;

use App\AccessRequest\Domain\Entity\AccessRequest;
use Symfony\Component\Uid\Uuid;

interface AccessRequestRepositoryInterface
{
    public function getById(Uuid $id): ?AccessRequest;

    /**
     * @return array{items: list<AccessRequest>, total: int}
     */
    public function search(?string $query, int $page, int $pageSize): array;
}