<?php

declare(strict_types=1);

/*
 * Domain repository port for users.
 * Application use cases depend on this contract; Doctrine remains an infrastructure adapter.
 */

namespace App\Security\Domain\Repository;

use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\EmailAddress;

interface UserRepositoryInterface
{
    public function findOneByEmail(EmailAddress $email): ?User;

    /** @return array{items: list<User>, total: int} */
    public function search(?bool $isActive, ?string $query, int $page, int $pageSize): array;

    public function getById(UserId $id): ?User;

    public function save(User $user): void;

    public function remove(User $user): void;
}
