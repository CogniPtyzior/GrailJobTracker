<?php

declare(strict_types=1);

/*
 * Frontend-compatible authentication response output.
 * The top-level user key is preserved while the mapping stays outside the domain aggregate.
 */

namespace App\Security\Api\Output;

use App\Security\Domain\Entity\User;
use DateTimeInterface;

final readonly class AuthenticatedUserOutput
{
    public function __construct(public AuthenticatedUserData $user)
    {
    }

    public static function fromDomain(User $user): self
    {
        return new self(new AuthenticatedUserData(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            firstName: $user->firstName()?->value(),
            lastName: $user->lastName()?->value(),
            roles: $user->roles()->toArray(),
            isActive: $user->isActive(),
            createdAt: $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            lastLoginAt: $user->getLastLoginAt()?->format(DateTimeInterface::ATOM),
        ));
    }
}
