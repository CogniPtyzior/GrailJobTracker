<?php

declare(strict_types=1);

/*
 * In-memory user repository for security unit tests.
 * It exercises the domain repository port without requiring Doctrine or the shared database.
 */

namespace App\Tests\Support\Fake;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\EmailAddress;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $usersById = [];
    public int $saveCalls = 0;

    public function add(User $user): void
    {
        $this->usersById[$user->getId()->toRfc4122()] = $user;
    }

    public function findOneByEmail(EmailAddress $email): ?User
    {
        foreach ($this->usersById as $user) {
            if ($user->getNormalizedEmail() === $email->normalizedValue()) {
                return $user;
            }
        }

        return null;
    }

    public function search(?bool $isActive, ?string $query, int $page, int $pageSize): array
    {
        return ['items' => array_values($this->usersById), 'total' => count($this->usersById)];
    }

    public function getById(UserId $id): ?User
    {
        return $this->usersById[$id->toRfc4122()] ?? null;
    }

    public function save(User $user): void
    {
        $this->saveCalls++;
        $this->add($user);
    }

    public function remove(User $user): void
    {
        unset($this->usersById[$user->getId()->toRfc4122()]);
    }

}
