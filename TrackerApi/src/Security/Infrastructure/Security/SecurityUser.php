<?php

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Entity\User;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    public function __construct(private User $domainUser)
    {
    }

    public function domainUser(): User
    {
        return $this->domainUser;
    }

    public function getRoles(): array
    {
        return $this->domainUser->roles()->toArray();
    }

    public function getPassword(): string
    {
        return $this->domainUser->getPassword();
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->domainUser->getNormalizedEmail();
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        $other = $user->domainUser;

        return $this->domainUser->getNormalizedEmail() === $other->getNormalizedEmail()
            && $this->domainUser->getPassword() === $other->getPassword()
            && $this->domainUser->isActive() === $other->isActive()
            && $this->domainUser->roles()->equals($other->roles());
    }
}