<?php

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ActiveUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        $this->checkIsActive($user);
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        $this->checkIsActive($user);
    }

    private function checkIsActive(UserInterface $user): void
    {
        if (!$user instanceof User || $user->isActive()) {
            return;
        }

        throw new DisabledException();
    }
}
