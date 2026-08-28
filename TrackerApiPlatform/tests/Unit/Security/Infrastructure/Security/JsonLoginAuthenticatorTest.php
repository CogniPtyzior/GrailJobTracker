<?php

declare(strict_types=1);

/*
 * Unit tests for the custom JSON login authenticator.
 * They lock the frontend email/password payload and the Symfony Passport authentication flow.
 */

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\JsonLoginAuthenticator;
use App\Security\Infrastructure\Security\SecurityJsonResponseFactory;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Clock\ClockInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryUserRepository;
use App\Tests\Support\Fake\InMemoryTransactionManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

it('supports the login API path only', function (): void {
    $authenticator = createJsonLoginAuthenticator();

    expect($authenticator->supports(Request::create('/api/auth/login', 'POST')))->toBeTrue()
        ->and($authenticator->supports(Request::create('/api/auth/login', 'GET')))->toBeFalse()
        ->and($authenticator->supports(Request::create('/api/auth/me', 'POST')))->toBeFalse();
});

it('creates a Symfony passport from JSON credentials', function (): void {
    $request = Request::create('/api/auth/login', 'POST', [], [], [], [], json_encode([
        'email' => 'john@example.com',
        'password' => 'secret',
    ], JSON_THROW_ON_ERROR));

    $passport = createJsonLoginAuthenticator()->authenticate($request);

    expect($passport)->toBeInstanceOf(Passport::class)
        ->and($passport->getBadge(PasswordCredentials::class))->toBeInstanceOf(PasswordCredentials::class);
});

it('rejects malformed JSON payloads', function (): void {
    $request = Request::create('/api/auth/login', 'POST', [], [], [], [], '{invalid-json');

    createJsonLoginAuthenticator()->authenticate($request);
})->throws(CustomUserMessageAuthenticationException::class, 'Invalid request payload.');

it('marks users as logged in on success', function (): void {
    $repository = new InMemoryUserRepository();
    $user = new User(EmailAddress::fromString('john@example.com'));
    $repository->add($user);
    $loggedAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');
    $authenticator = createJsonLoginAuthenticator($repository, $loggedAt);
    $token = new UsernamePasswordToken(new SecurityUser($user), 'main', $user->getRoles());

    $response = $authenticator->onAuthenticationSuccess(Request::create('/api/auth/login'), $token, 'main');

    expect($user->getLastLoginAt())->toBe($loggedAt)
        ->and($repository->saveCalls)->toBe(1)
        ->and($response?->getStatusCode())->toBe(200);
});


it('returns a throttling response for too many login attempts', function (): void {
    $response = createJsonLoginAuthenticator()->onAuthenticationFailure(
        Request::create('/api/auth/login'),
        new TooManyLoginAttemptsAuthenticationException(),
    );

    expect($response?->getStatusCode())->toBe(429)
        ->and(json_decode((string) $response?->getContent(), true, 512, JSON_THROW_ON_ERROR)['message'])
        ->toBe('Too many login attempts. Please retry later.');
});

function createJsonLoginAuthenticator(
    ?InMemoryUserRepository $repository = null,
    ?DateTimeImmutable $now = null,
): JsonLoginAuthenticator {
    $clock = new class($now ?? new DateTimeImmutable('2026-01-01T00:00:00+00:00')) implements ClockInterface {
        public function __construct(private readonly DateTimeImmutable $now)
        {
        }

        public function now(): DateTimeImmutable
        {
            return $this->now;
        }
    };

    return new JsonLoginAuthenticator($clock, $repository ?? new InMemoryUserRepository(), new InMemoryTransactionManager(), new SecurityJsonResponseFactory());
}


