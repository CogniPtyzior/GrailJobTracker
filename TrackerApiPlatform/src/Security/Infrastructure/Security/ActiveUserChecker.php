<?php

declare(strict_types=1);

/*
 * Symfony user checker for active accounts.
 * It rejects inactive domain users during the authentication lifecycle.
 */

namespace App\Security\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ActiveUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        $this->checkIsActive($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->checkIsActive($user);
    }

    private function checkIsActive(UserInterface $user): void
    {
        if (!$user instanceof SecurityUser || $user->domainUser()->isActive()) {
            return;
        }

        throw new DisabledException();
    }
}
