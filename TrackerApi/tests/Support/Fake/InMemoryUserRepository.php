<?php

namespace App\Tests\Support\Fake;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<string, User> */
    private array $usersById = [];

    /** @param list<User> $users */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->save($user);
        }
    }

    public function findOneByNormalizedEmail(string $normalizedEmail): ?User
    {
        foreach ($this->usersById as $user) {
            if ($user->getNormalizedEmail() === $normalizedEmail) {
                return $user;
            }
        }

        return null;
    }

    public function search(?bool $isActive, ?string $query, int $page, int $pageSize): array
    {
        $items = array_values($this->usersById);

        if ($isActive !== null) {
            $items = array_values(array_filter($items, static fn (User $user): bool => $user->isActive() === $isActive));
        }

        if ($query !== null && $query !== '') {
            $term = mb_strtolower(trim($query));
            $items = array_values(array_filter($items, static function (User $user) use ($term): bool {
                return str_contains(mb_strtolower($user->getEmail()), $term)
                    || str_contains(mb_strtolower($user->getFirstName() ?? ''), $term)
                    || str_contains(mb_strtolower($user->getLastName() ?? ''), $term);
            }));
        }

        usort($items, static function (User $left, User $right): int {
            if ($left->isActive() !== $right->isActive()) {
                return $left->isActive() ? -1 : 1;
            }

            return $right->getCreatedAt() <=> $left->getCreatedAt();
        });

        $offset = max(0, ($page - 1) * $pageSize);

        return [
            'items' => array_slice($items, $offset, $pageSize),
            'total' => count($items),
        ];
    }

    public function getById(Uuid $id): ?User
    {
        return $this->usersById[$id->toRfc4122()] ?? null;
    }

    public function save(User $user): void
    {
        $this->usersById[$user->getId()->toRfc4122()] = $user;
    }

    public function remove(User $user): void
    {
        unset($this->usersById[$user->getId()->toRfc4122()]);
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->usersById);
    }
}