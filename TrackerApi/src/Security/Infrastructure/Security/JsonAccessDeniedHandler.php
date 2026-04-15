<?php

namespace App\Security\Infrastructure\Security;

use App\Shared\Infrastructure\Http\ApiJsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class JsonAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        return ApiJsonResponse::error('Access denied.', Response::HTTP_FORBIDDEN);
    }
}
