<?php

declare(strict_types=1);

/*
 * Functional tests for admin access request endpoints.
 * They exercise API Platform providers/processors through HTTP against the PostgreSQL test database.
 */

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Security\Domain\Entity\User;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

const ADMIN_ACCESS_REQUEST_EMAIL_PREFIX = 'p21-admin-';

afterEach(function (): void {
    if (self::$booted) {
        deleteAdminAccessRequestData();
    }
});

it('lists access requests for admins with the legacy pagination envelope', function (): void {
    skipAdminAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensureAdminAccessRequestDatabaseAvailable();
    deleteAdminAccessRequestData();
    loginAdminAccessRequestUser($client, true);
    persistAdminAccessRequest('list-one@example.com', 'Acme List');
    persistAdminAccessRequest('list-two@example.com', 'Globex List');

    $client->request('GET', '/api/admin/access-requests?page=1&pageSize=10&query=list', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $payload = adminAccessRequestJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($payload['items'])->toHaveCount(2)
        ->and($payload['page'])->toBe(1)
        ->and($payload['pageSize'])->toBe(10)
        ->and($payload['total'])->toBe(2);
});

it('approves an access request by creating a user and deleting the request', function (): void {
    skipAdminAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensureAdminAccessRequestDatabaseAvailable();
    deleteAdminAccessRequestData();
    loginAdminAccessRequestUser($client, true);
    $accessRequest = persistAdminAccessRequest('approve@example.com', 'Acme Approve', 'Jane', 'Doe');

    $client->request(
        'POST',
        '/api/admin/access-requests/'.$accessRequest->getId()->toRfc4122().'/approve',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['password' => 'Password1!'], JSON_THROW_ON_ERROR),
    );

    $payload = adminAccessRequestJson($client);

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($payload['item']['email'])->toBe(ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'approve@example.com')
        ->and(countAdminAccessRequests())->toBe(0)
        ->and(countAdminUsersByEmail(ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'approve@example.com'))->toBe(1);
});

it('deletes access requests for admins', function (): void {
    skipAdminAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensureAdminAccessRequestDatabaseAvailable();
    deleteAdminAccessRequestData();
    loginAdminAccessRequestUser($client, true);
    $accessRequest = persistAdminAccessRequest('delete@example.com', 'Acme Delete');

    $client->request(
        'DELETE',
        '/api/admin/access-requests/'.$accessRequest->getId()->toRfc4122(),
        server: ['HTTP_ACCEPT' => 'application/json'],
    );

    expect($client->getResponse()->getStatusCode())->toBe(204)
        ->and(countAdminAccessRequests())->toBe(0);
});

it('denies admin access request endpoints to non-admin authenticated users', function (): void {
    skipAdminAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensureAdminAccessRequestDatabaseAvailable();
    deleteAdminAccessRequestData();
    loginAdminAccessRequestUser($client, false);

    $client->request('GET', '/api/admin/access-requests', server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(403);
});

function persistAdminAccessRequest(
    string $emailSuffix,
    string $companyName,
    ?string $firstName = null,
    ?string $lastName = null,
): AccessRequest {
    /** @var AccessRequestRepositoryInterface $repository */
    $repository = test()->getContainer()->get(AccessRequestRepositoryInterface::class);
    $accessRequest = AccessRequest::submit(
        EmailAddress::fromString(ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.$emailSuffix),
        AccessRequestCompanyName::fromString($companyName),
        AccessRequestReason::fromString('This request should be managed by an administrator.'),
        PersonName::fromNullable($firstName),
        PersonName::fromNullable($lastName),
    );

    $repository->save($accessRequest);
    $repository->flush();

    return $accessRequest;
}

function loginAdminAccessRequestUser(KernelBrowser $client, bool $isAdmin): void
{
    $email = ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.($isAdmin ? 'admin' : 'user').'@example.com';
    $user = new User(EmailAddress::fromString($email));
    $user->updateAdminRole($isAdmin);
    $user->setPasswordHash(adminAccessRequestPasswordHasher()->hash($user, 'Password1!'));
    adminAccessRequestUsers()->save($user);
    adminAccessRequestUsers()->flush();

    $client->request(
        'POST',
        '/api/auth/login',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['email' => $email, 'password' => 'Password1!'], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(200);
}


function adminAccessRequestUsers(): UserRepositoryInterface
{
    return test()->getContainer()->get(UserRepositoryInterface::class);
}

function adminAccessRequestPasswordHasher(): PasswordHasherInterface
{
    return test()->getContainer()->get(PasswordHasherInterface::class);
}
function skipAdminAccessRequestIfPdoMissing(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for admin access request functional tests.');
    }
}

function ensureAdminAccessRequestDatabaseAvailable(): void
{
    try {
        adminAccessRequestConnection()->executeQuery('SELECT 1');
        adminAccessRequestConnection()->executeQuery('SELECT COUNT(*) FROM trackers.access_requests');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function countAdminAccessRequests(): int
{
    return (int) adminAccessRequestConnection()->fetchOne(
        'SELECT COUNT(*) FROM trackers.access_requests WHERE normalized_email LIKE ?',
        [ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'%'],
    );
}


function countAdminUsersByEmail(string $email): int
{
    return (int) adminAccessRequestConnection()->fetchOne(
        'SELECT COUNT(*) FROM trackers.users WHERE normalized_email = ?',
        [mb_strtolower($email)],
    );
}
function countAdminUsers(): int
{
    return (int) adminAccessRequestConnection()->fetchOne(
        'SELECT COUNT(*) FROM trackers.users WHERE normalized_email LIKE ?',
        [ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'%'],
    );
}

function deleteAdminAccessRequestData(): void
{
    try {
        $connection = adminAccessRequestConnection();
        $connection->executeStatement(
            'DELETE FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'%'],
        );
        $connection->executeStatement(
            'DELETE FROM trackers.users WHERE normalized_email LIKE ?',
            [ADMIN_ACCESS_REQUEST_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

/** @return array<string, mixed> */
function adminAccessRequestJson(KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();

    expect($content)->toBeString();

    $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toBeArray();

    return $payload;
}

function adminAccessRequestConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}
