<?php

declare(strict_types=1);

/*
 * Functional tests for authentication endpoints.
 * They preserve legacy login, current-user, inactive-user and throttling behavior through Symfony Security and API Platform.
 */

use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

const AUTH_ENDPOINT_EMAIL_PREFIX = 'p24-auth-';
const AUTH_ENDPOINT_PASSWORD = 'Password1!';

afterEach(function (): void {
    if (self::$booted) {
        deleteAuthEndpointUsers();
    }
});

it('allows active users to login and read the current user endpoint', function (): void {
    skipAuthEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAuthEndpointDatabaseAvailable();
    deleteAuthEndpointUsers();
    $email = AUTH_ENDPOINT_EMAIL_PREFIX.'active@example.com';
    persistAuthEndpointUser($email);

    authEndpointLogin($client, $email);
    $loginPayload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($loginPayload['user']['email'])->toBe($email)
        ->and($loginPayload['user']['isActive'])->toBeTrue()
        ->and($loginPayload['user']['lastLoginAt'])->not->toBeNull();

    $client->request('GET', '/api/auth/me', server: ['HTTP_ACCEPT' => 'application/json']);
    $mePayload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($mePayload['user']['email'])->toBe($email)
        ->and($mePayload['user']['isActive'])->toBeTrue();
});

it('rejects inactive users and keeps the current user endpoint anonymous', function (): void {
    skipAuthEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAuthEndpointDatabaseAvailable();
    deleteAuthEndpointUsers();
    $email = AUTH_ENDPOINT_EMAIL_PREFIX.'inactive@example.com';
    persistAuthEndpointUser($email, isActive: false);

    authEndpointLogin($client, $email);
    $loginPayload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($loginPayload['message'])->toBe('Invalid credentials.')
        ->and($loginPayload['details'])->toBe([]);

    $client->request('GET', '/api/auth/me', server: ['HTTP_ACCEPT' => 'application/json']);
    $mePayload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($mePayload['message'])->toBe('Authentication required.')
        ->and($mePayload['details'])->toBe([]);
});

it('rejects an existing session after user deactivation', function (): void {
    skipAuthEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAuthEndpointDatabaseAvailable();
    deleteAuthEndpointUsers();
    $email = AUTH_ENDPOINT_EMAIL_PREFIX.'deactivated-session@example.com';
    $user = persistAuthEndpointUser($email);

    authEndpointLogin($client, $email);
    expect($client->getResponse()->getStatusCode())->toBe(200);

    $managedUser = authEndpointUsers()->getById($user->getId());
    expect($managedUser)->toBeInstanceOf(User::class);
    $managedUser->deactivate();
    authEndpointUsers()->save($managedUser);
    authEndpointUsers()->flush();

    $client->request('GET', '/api/auth/me', server: ['HTTP_ACCEPT' => 'application/json']);
    $payload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($payload['message'])->toBe('Authentication required.')
        ->and($payload['details'])->toBe([]);
});

it('blocks login after repeated failed attempts', function (): void {
    skipAuthEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAuthEndpointDatabaseAvailable();
    deleteAuthEndpointUsers();
    $email = AUTH_ENDPOINT_EMAIL_PREFIX.bin2hex(random_bytes(4)).'@example.com';
    $remoteAddress = '198.51.100.'.random_int(1, 254);
    persistAuthEndpointUser($email);

    authEndpointLogin($client, $email, 'wrong-password', $remoteAddress);
    expect($client->getResponse()->getStatusCode())->toBe(401);

    authEndpointLogin($client, $email, 'wrong-password', $remoteAddress);
    expect($client->getResponse()->getStatusCode())->toBe(401);

    authEndpointLogin($client, $email, AUTH_ENDPOINT_PASSWORD, $remoteAddress);
    $payload = authEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(429)
        ->and($payload['message'])->toBe('Too many login attempts. Please retry later.')
        ->and($payload['details'])->toBe([]);
});

function persistAuthEndpointUser(string $email, bool $isActive = true): User
{
    $user = new User(EmailAddress::fromString($email));
    $isActive ? $user->activate() : $user->deactivate();
    $user->setPasswordHash(authEndpointPasswordHasher()->hash($user, AUTH_ENDPOINT_PASSWORD));
    authEndpointUsers()->save($user);
    authEndpointUsers()->flush();

    return $user;
}

function authEndpointLogin(
    KernelBrowser $client,
    string $email,
    string $password = AUTH_ENDPOINT_PASSWORD,
    ?string $remoteAddress = null,
): void {
    $client->request(
        'POST',
        '/api/auth/login',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => $remoteAddress ?? '203.0.113.'.random_int(1, 254),
        ],
        content: json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR),
    );
}

function skipAuthEndpointIfPdoMissing(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for authentication functional tests.');
    }
}

function ensureAuthEndpointDatabaseAvailable(): void
{
    try {
        authEndpointConnection()->executeQuery('SELECT 1');
        authEndpointConnection()->executeQuery('SELECT COUNT(*) FROM trackers.users');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function deleteAuthEndpointUsers(): void
{
    try {
        authEndpointConnection()->executeStatement(
            'DELETE FROM trackers.users WHERE normalized_email LIKE ?',
            [AUTH_ENDPOINT_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

/** @return array<string, mixed> */
function authEndpointJson(KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();

    expect($content)->toBeString();

    $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toBeArray();

    return $payload;
}

function authEndpointUsers(): UserRepositoryInterface
{
    return test()->getContainer()->get(UserRepositoryInterface::class);
}

function authEndpointPasswordHasher(): PasswordHasherInterface
{
    return test()->getContainer()->get(PasswordHasherInterface::class);
}

function authEndpointConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}
