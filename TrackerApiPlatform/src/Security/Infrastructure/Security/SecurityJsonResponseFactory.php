<?php

declare(strict_types=1);

/*
 * Small response factory for Symfony security handlers.
 * It preserves the frontend JSON contract without introducing legacy controllers.
 */

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SecurityJsonResponseFactory
{
    public function success(array $data = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    public function error(string $message, int $status, array $details = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    public function authenticatedUser(User $user): JsonResponse
    {
        return $this->success([
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'firstName' => $user->firstName()?->value(),
                'lastName' => $user->lastName()?->value(),
                'roles' => $user->roles()->toArray(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'lastLoginAt' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
