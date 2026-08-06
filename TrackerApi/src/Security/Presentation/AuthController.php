<?php

namespace App\Security\Presentation;

use App\Security\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Authenticate a user with email and password.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string')
                ]
            )
        ),
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Authenticated.')]
    #[OA\Response(response: 401, description: 'Invalid credentials.')]
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('The login route is handled by Symfony security.');
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Return the current authenticated user.',
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Current user returned.')]
    #[OA\Response(response: 401, description: 'Authentication required.')]
    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            return ApiJsonResponse::error('Authentication required.', Response::HTTP_UNAUTHORIZED);
        }

        return ApiJsonResponse::success([
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

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout the current user.',
        tags: ['Authentication']
    )]
    #[OA\Response(response: 204, description: 'Logged out.')]
    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('The logout route is handled by Symfony security.');
    }
}

