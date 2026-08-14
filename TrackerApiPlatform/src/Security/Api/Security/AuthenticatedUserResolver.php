<?php

declare(strict_types=1);

/*
 * API adapter helper for resolving the authenticated domain user from Symfony Security.
 * Providers and processors use it to avoid duplicating token inspection logic.
 */

namespace App\Security\Api\Security;

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

final readonly class AuthenticatedUserResolver
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function requireUser(): User
    {
        $securityUser = $this->tokenStorage->getToken()?->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('Authentication required.');
        }

        return $securityUser->domainUser();
    }
}
