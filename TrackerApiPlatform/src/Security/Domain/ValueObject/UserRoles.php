<?php

declare(strict_types=1);

/*
 * Domain value object for Symfony security roles.
 * It normalizes persisted role lists and guarantees that every user keeps ROLE_USER.
 */

namespace App\Security\Domain\ValueObject;

final readonly class UserRoles
{
    /** @param list<string> $values */
    private function __construct(private array $values)
    {
    }

    /** @param list<string> $roles */
    public static function fromArray(array $roles): self
    {
        $cleanRoles = [];

        foreach ($roles as $role) {
            $role = trim($role);

            if ($role === '' || in_array($role, $cleanRoles, true)) {
                continue;
            }

            $cleanRoles[] = $role;
        }

        if (!in_array('ROLE_USER', $cleanRoles, true)) {
            $cleanRoles[] = 'ROLE_USER';
        }

        return new self($cleanRoles);
    }

    public static function regularUser(): self
    {
        return new self(['ROLE_USER']);
    }

    public static function admin(): self
    {
        return new self(['ROLE_ADMIN', 'ROLE_USER']);
    }

    /** @return list<string> */
    public function toArray(): array
    {
        return $this->values;
    }

    public function equals(self $other): bool
    {
        return $this->values === $other->values;
    }
}
