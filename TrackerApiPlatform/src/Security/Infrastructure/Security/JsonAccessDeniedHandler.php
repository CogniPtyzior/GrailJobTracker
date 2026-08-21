<?php

declare(strict_types=1);

/*
 * Access denied handler for JSON API clients.
 * Authorization errors stay in Symfony security while the response shape remains frontend-compatible.
 */

namespace App\Security\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final readonly class JsonAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private SecurityJsonResponseFactory $responseFactory)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return $this->responseFactory->error('Access denied.', Response::HTTP_FORBIDDEN);
    }
}
