<?php

namespace App\Security\Infrastructure\Security;

use App\Security\Infrastructure\Security\SecurityUser;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function onAuthenticationSuccess(\Symfony\Component\HttpFoundation\Request $request, TokenInterface $token): ?Response
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $token->getUser();
        $user = $securityUser->domainUser();
        $user->markLoggedIn(new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
        $this->userRepository->save($user);
        $this->userRepository->flush();

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
}
