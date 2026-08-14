<?php

declare(strict_types=1);

/*
 * Symfony Security adapter for the application password hasher port.
 * It wraps the domain user in SecurityUser so generated hashes remain compatible with JSON login.
 */

namespace App\Security\Infrastructure\Security;

use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function hash(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new SecurityUser($user), $plainPassword);
    }
}
