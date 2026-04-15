<?php

namespace App\Security\Infrastructure\Security;

use App\Shared\Infrastructure\Http\ApiJsonResponse;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class JsonLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response    {
        return ApiJsonResponse::error('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
    }
}
