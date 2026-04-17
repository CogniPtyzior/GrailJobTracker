<?php

namespace App\Security\Domain\Repository;

use App\Security\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findOneByNormalizedEmail(string $normalizedEmail): ?User;

    /**
     * @return array{items: list<User>, total: int}
     */
    public function search(?bool $isActive, ?string $query, int $page, int $pageSize): array;

    public function getById(Uuid $id): ?User;
}