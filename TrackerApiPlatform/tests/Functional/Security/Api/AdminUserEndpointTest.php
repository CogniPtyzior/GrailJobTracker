<?php

declare(strict_types=1);

/*
 * Functional tests for admin user endpoints.
 * They exercise API Platform providers/processors through HTTP against the shared PostgreSQL test database.
 */

use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

const ADMIN_USER_EMAIL_PREFIX = 'p22-admin-user-';

afterEach(function (): void {
    if (self::$booted) {
        deleteAdminUserEndpointData();
    }
});

it('lists admin users with the legacy pagination envelope', function (): void {
    skipAdminUserEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAdminUserEndpointDatabaseAvailable();
    deleteAdminUserEndpointData();
    loginAdminUserEndpointUser($client, true);
    persistAdminUserEndpointUser('list-one@example.com');
    persistAdminUserEndpointUser('list-two@example.com');

    $client->request('GET', '/api/admin/users?page=1&pageSize=10&query=list', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $payload = adminUserEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($payload['items'])->toHaveCount(2)
        ->and($payload['page'])->toBe(1)
        ->and($payload['pageSize'])->toBe(10)
        ->and($payload['total'])->toBe(2);
});

it('creates, updates and deletes users through admin endpoints', function (): void {
    skipAdminUserEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAdminUserEndpointDatabaseAvailable();
    deleteAdminUserEndpointData();
    loginAdminUserEndpointUser($client, true);

    $client->request(
        'POST',
        '/api/admin/users',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode([
            'email' => ADMIN_USER_EMAIL_PREFIX.'managed@example.com',
            'password' => 'Password1!',
            'firstName' => 'Managed',
            'lastName' => 'User',
            'isActive' => true,
            'isAdmin' => false,
        ], JSON_THROW_ON_ERROR),
    );
    $created = adminUserEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(201)
        ->and($created['item']['email'])->toBe(ADMIN_USER_EMAIL_PREFIX.'managed@example.com')
        ->and($created['item']['roles'])->toBe(['ROLE_USER']);

    $client->request(
        'PUT',
        '/api/admin/users/'.$created['item']['id'],
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['firstName' => 'Updated', 'isAdmin' => true], JSON_THROW_ON_ERROR),
    );
    $updated = adminUserEndpointJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($updated['item']['firstName'])->toBe('Updated')
        ->and($updated['item']['roles'])->toBe(['ROLE_ADMIN', 'ROLE_USER']);

    $client->request('DELETE', '/api/admin/users/'.$created['item']['id'], server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(204)
        ->and(countAdminUserEndpointUsersByEmail(ADMIN_USER_EMAIL_PREFIX.'managed@example.com'))->toBe(0);
});

it('denies admin user endpoints to non-admin authenticated users', function (): void {
    skipAdminUserEndpointIfPdoMissing();
    $client = self::createClient();
    ensureAdminUserEndpointDatabaseAvailable();
    deleteAdminUserEndpointData();
    loginAdminUserEndpointUser($client, false);

    $client->request('GET', '/api/admin/users', server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(403);
});

function loginAdminUserEndpointUser(KernelBrowser $client, bool $isAdmin): User
{
    $email = ADMIN_USER_EMAIL_PREFIX.($isAdmin ? 'admin' : 'user').'@example.com';
    $user = persistAdminUserEndpointUser($email, $isAdmin);

    $client->request(
        'POST',
        '/api/auth/login',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['email' => $email, 'password' => 'Password1!'], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(200);

    return $user;
}

function persistAdminUserEndpointUser(string $emailSuffix, bool $isAdmin = false): User
{
    $email = str_starts_with($emailSuffix, ADMIN_USER_EMAIL_PREFIX) ? $emailSuffix : ADMIN_USER_EMAIL_PREFIX.$emailSuffix;
    $user = new User(EmailAddress::fromString($email));
    $user->updateAdminRole($isAdmin);
    $user->setPasswordHash(adminUserEndpointPasswordHasher()->hash($user, 'Password1!'));
    adminUserEndpointUsers()->save($user);
    adminUserEndpointUsers()->flush();

    return $user;
}

function skipAdminUserEndpointIfPdoMissing(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for admin user functional tests.');
    }
}

function ensureAdminUserEndpointDatabaseAvailable(): void
{
    try {
        adminUserEndpointConnection()->executeQuery('SELECT 1');
        adminUserEndpointConnection()->executeQuery('SELECT COUNT(*) FROM trackers.users');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function deleteAdminUserEndpointData(): void
{
    try {
        adminUserEndpointConnection()->executeStatement(
            'DELETE FROM trackers.users WHERE normalized_email LIKE ?',
            [ADMIN_USER_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

function countAdminUserEndpointUsersByEmail(string $email): int
{
    return (int) adminUserEndpointConnection()->fetchOne(
        'SELECT COUNT(*) FROM trackers.users WHERE normalized_email = ?',
        [mb_strtolower($email)],
    );
}

/** @return array<string, mixed> */
function adminUserEndpointJson(KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();

    expect($content)->toBeString();

    $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toBeArray();

    return $payload;
}

function adminUserEndpointUsers(): UserRepositoryInterface
{
    return test()->getContainer()->get(UserRepositoryInterface::class);
}

function adminUserEndpointPasswordHasher(): PasswordHasherInterface
{
    return test()->getContainer()->get(PasswordHasherInterface::class);
}

function adminUserEndpointConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}


