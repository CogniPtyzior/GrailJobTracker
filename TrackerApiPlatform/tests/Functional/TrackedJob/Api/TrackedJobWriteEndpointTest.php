<?php

declare(strict_types=1);

/*
 * Functional tests for tracked job write endpoints.
 * They exercise API Platform processors through HTTP when the PostgreSQL test database is available.
 */

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

const TRACKED_JOB_WRITE_EMAIL_PREFIX = 'tracked-p14-';
const TRACKED_JOB_WRITE_PASSWORD = 'Password1!';

afterEach(function (): void {
    if (self::$booted) {
        deleteTrackedJobWriteData();
    }
});

it('creates updates and deletes a tracked job through API Platform processors', function (): void {
    $client = self::createClient();
    ensureTrackedJobWriteDatabaseAvailable();
    createTrackedJobWriteUser(TRACKED_JOB_WRITE_EMAIL_PREFIX.'writer@example.com');
    loginTrackedJobWriteUser($client, TRACKED_JOB_WRITE_EMAIL_PREFIX.'writer@example.com');

    $client->request(
        'POST',
        '/api/tracked-jobs',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode([
            'company' => ' Acme ',
            'title' => ' Backend Engineer ',
            'contractType' => 'CDD',
            'remoteMode' => 'FULL',
            'offerUrl' => ' https://example.com/job ',
            'applicationDate' => '2026-07-28T00:00:00.000Z',
            'subjectiveRelevance' => '9',
        ], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(201);
    $created = trackedJobWriteJson($client);
    $id = $created['item']['id'];

    expect($created['item']['company'])->toBe('Acme')
        ->and($created['item']['title'])->toBe('Backend Engineer')
        ->and($created['item']['offerUrl'])->toBe('https://example.com/job')
        ->and($created['item']['applicationDate'])->toBe('2026-07-28T00:00:00+00:00')
        ->and($created['item']['subjectiveRelevance'])->toBe(9);

    $client->request(
        'PUT',
        '/api/tracked-jobs/'.$id,
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['company' => 'Updated'], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(200);
    $updated = trackedJobWriteJson($client);

    expect($updated['item']['id'])->toBe($id)
        ->and($updated['item']['company'])->toBe('Updated');

    $client->request('DELETE', '/api/tracked-jobs/'.$id, server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(204);
});

it('rejects invalid tracked job write payloads through Symfony validation', function (): void {
    $client = self::createClient();
    ensureTrackedJobWriteDatabaseAvailable();
    createTrackedJobWriteUser(TRACKED_JOB_WRITE_EMAIL_PREFIX.'invalid@example.com');
    loginTrackedJobWriteUser($client, TRACKED_JOB_WRITE_EMAIL_PREFIX.'invalid@example.com');

    $client->request(
        'POST',
        '/api/tracked-jobs',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode([
            'applicationDate' => '2026-02-31T00:00:00.000Z',
            'notes' => str_repeat('a', 10001),
        ], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBeGreaterThanOrEqual(400)
        ->and($client->getResponse()->getStatusCode())->toBeLessThan(500);
});

function ensureTrackedJobWriteDatabaseAvailable(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for tracked job write functional tests.');
    }

    try {
        trackedJobWriteConnection()->executeQuery('SELECT 1');
        trackedJobWriteConnection()->executeQuery('SELECT COUNT(*) FROM trackers.users');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function createTrackedJobWriteUser(string $email): void
{
    deleteTrackedJobWriteData();

    /** @var UserRepositoryInterface $users */
    $users = test()->getContainer()->get(UserRepositoryInterface::class);
    /** @var UserPasswordHasherInterface $passwordHasher */
    $passwordHasher = test()->getContainer()->get(UserPasswordHasherInterface::class);
    $user = new User(EmailAddress::fromString($email));

    $user->setPasswordHash($passwordHasher->hashPassword(new SecurityUser($user), TRACKED_JOB_WRITE_PASSWORD));
    $users->save($user);
    $users->flush();
}

function loginTrackedJobWriteUser(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $email): void
{
    $client->request(
        'POST',
        '/api/auth/login',
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        content: json_encode(['email' => $email, 'password' => TRACKED_JOB_WRITE_PASSWORD], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(200);
}

/** @return array<string, mixed> */
function trackedJobWriteJson(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): array
{
    $content = $client->getResponse()->getContent();

    expect($content)->toBeString();

    $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    expect($payload)->toBeArray();

    return $payload;
}

function deleteTrackedJobWriteData(): void
{
    try {
        $connection = trackedJobWriteConnection();
        $connection->executeStatement(
            'DELETE FROM trackers.tracked_jobs WHERE owner_id IN (
                SELECT id FROM trackers.users WHERE normalized_email LIKE ?
            )',
            [TRACKED_JOB_WRITE_EMAIL_PREFIX.'%'],
        );
        $connection->executeStatement(
            'DELETE FROM trackers.users WHERE normalized_email LIKE ?',
            [TRACKED_JOB_WRITE_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

function trackedJobWriteConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}