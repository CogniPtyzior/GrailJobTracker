<?php

declare(strict_types=1);

/*
 * API Platform operation host for authentication endpoints.
 * The resource exposes the HTTP contract while Symfony Security keeps ownership of session authentication.
 */

namespace App\Security\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Security\Api\Input\LoginInput;
use App\Security\Api\Output\AuthenticatedUserOutput;
use App\Security\Api\Processor\LoginHandledBySecurityProcessor;
use App\Security\Api\Provider\CurrentUserProvider;

#[ApiResource(
    shortName: 'Authentication',
    operations: [
        new Post(
            uriTemplate: '/auth/login',
            status: 200,
            input: LoginInput::class,
            output: AuthenticatedUserOutput::class,
            read: false,
            validate: false,
            processor: LoginHandledBySecurityProcessor::class,
            name: 'auth_login',
        ),
        new Get(
            uriTemplate: '/auth/me',
            output: AuthenticatedUserOutput::class,
            provider: CurrentUserProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'auth_me',
        ),
    ],
)]
final class AuthenticationResource
{
}


