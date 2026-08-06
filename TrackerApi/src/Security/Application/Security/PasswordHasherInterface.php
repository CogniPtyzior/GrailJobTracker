<?php

namespace App\Security\Application\Security;

use App\Security\Domain\Entity\User;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;
}