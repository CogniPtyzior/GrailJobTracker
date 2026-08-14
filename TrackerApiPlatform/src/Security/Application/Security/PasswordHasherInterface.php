<?php

declare(strict_types=1);

/*
 * Application port for hashing domain user passwords.
 * It lets user provisioning use Symfony-compatible hashes without depending on Symfony Security directly.
 */

namespace App\Security\Application\Security;

use App\Security\Domain\Entity\User;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;
}
