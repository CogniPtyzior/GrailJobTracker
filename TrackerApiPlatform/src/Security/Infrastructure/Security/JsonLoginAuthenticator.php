<?php

declare(strict_types=1);

/*
 * Custom Symfony authenticator for JSON login requests.
 * It keeps authentication Symfony-native while preserving the frontend email/password JSON payload.
 */

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Clock\ClockInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

final class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ClockInterface $clock,
        private readonly UserRepositoryInterface $userRepository,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly SecurityJsonResponseFactory $responseFactory,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST) && $request->getPathInfo() === '/api/auth/login';
    }

    public function authenticate(Request $request): Passport
    {
        $payload = $this->decodePayload($request);
        $email = $this->readRequiredString($payload, 'email');
        $password = $this->readRequiredString($payload, 'password');

        return new Passport(new UserBadge($email), new PasswordCredentials($password));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $securityUser = $token->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw new AuthenticationException('Authenticated token does not contain a domain security user.');
        }

        $user = $securityUser->domainUser();
        $this->transactionManager->transactional(function () use ($user): void {
            $user->markLoggedIn($this->clock->now());
            $this->userRepository->save($user);
        });

        return $this->responseFactory->authenticatedUser($user);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return $this->responseFactory->error(
                'Too many login attempts. Please retry later.',
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        return $this->responseFactory->error('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
    }

    /** @return array<string, mixed> */
    private function decodePayload(Request $request): array
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CustomUserMessageAuthenticationException('Invalid request payload.');
        }

        if (!is_array($payload)) {
            throw new CustomUserMessageAuthenticationException('Invalid request payload.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    private function readRequiredString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (!is_string($value) || trim($value) === '') {
            throw new CustomUserMessageAuthenticationException('Invalid credentials.');
        }

        return $value;
    }
}
