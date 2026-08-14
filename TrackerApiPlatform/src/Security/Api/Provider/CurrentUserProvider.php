<?php

declare(strict_types=1);

/*
 * API Platform provider for the current authenticated user operation.
 * It reads the Symfony security token and maps the domain user to the frontend-compatible output DTO.
 */

namespace App\Security\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\Api\Output\AuthenticatedUserOutput;
use App\Security\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/** @implements ProviderInterface<AuthenticatedUserOutput> */
final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AuthenticatedUserOutput
    {
        $securityUser = $this->tokenStorage->getToken()?->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw new AuthenticationCredentialsNotFoundException('Authentication required.');
        }

        return AuthenticatedUserOutput::fromDomain($securityUser->domainUser());
    }
}
