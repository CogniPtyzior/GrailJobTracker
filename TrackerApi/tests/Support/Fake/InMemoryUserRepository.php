<?php

namespace App\Tests\Support\Fake;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\EmailAddress;

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
        $items = array_values($this->usersById);

        if ($isActive !== null) {
            $items = array_values(array_filter($items, static fn (User $user): bool => $user->isActive() === $isActive));
        }

        if ($query !== null && $query !== '') {
            $term = mb_strtolower(trim($query));
            $items = array_values(array_filter($items, static function (User $user) use ($term): bool {
                return str_contains(mb_strtolower($user->getEmail()), $term)
                    || str_contains(mb_strtolower($user->firstName()?->value() ?? ''), $term)
                    || str_contains(mb_strtolower($user->lastName()?->value() ?? ''), $term);
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

    public function getById(UserId $id): ?User
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

    public function flush(): void
    {
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_values($this->usersById);
    }
}


