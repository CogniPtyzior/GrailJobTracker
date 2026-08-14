<?php

declare(strict_types=1);

/*
 * Authentication entry point for JSON API clients.
 * It returns the frontend-compatible unauthenticated response for protected API routes.
 */

namespace App\Security\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class JsonAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(private SecurityJsonResponseFactory $responseFactory)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->responseFactory->error('Authentication required.', Response::HTTP_UNAUTHORIZED);
    }
}
