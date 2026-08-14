<?php

declare(strict_types=1);

/*
 * Functional test for public access request asynchronous notification dispatch.
 * It verifies API Platform creation stores the request and routes the notification message to Messenger.
 */

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

const ACCESS_REQUEST_ASYNC_EMAIL_PREFIX = 'p20-async-';

afterEach(function (): void {
    if (self::$booted) {
        deleteAsyncAccessRequestData();
    }
});

it('dispatches an async notification message when a public access request is created', function (): void {
    skipAsyncAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensureAsyncAccessRequestDatabaseAvailable();
    deleteAsyncAccessRequestData();
    asyncAccessRequestTransport()->reset();
    $email = ACCESS_REQUEST_ASYNC_EMAIL_PREFIX.random_int(1000, 9999).'@example.com';

    $client->request(
        'POST',
        '/api/access-requests',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => '198.51.100.'.random_int(1, 254),
        ],
        content: json_encode([
            'email' => $email,
            'companyName' => 'Acme',
            'reason' => 'I would like to try the tracker for my job search.',
        ], JSON_THROW_ON_ERROR),
    );

    expect($client->getResponse()->getStatusCode())->toBe(201);

    $accessRequestId = findAsyncAccessRequestId($email);
    $sentMessages = asyncAccessRequestTransport()->getSent();

    expect($accessRequestId)->not->toBeNull()
        ->and($sentMessages)->toHaveCount(1)
        ->and($sentMessages[0]->getMessage())->toBeInstanceOf(SendAccessRequestNotification::class)
        ->and($sentMessages[0]->getMessage()->accessRequestId)->toBe($accessRequestId);
});

function skipAsyncAccessRequestIfPdoMissing(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for access request async notification tests.');
    }
}

function ensureAsyncAccessRequestDatabaseAvailable(): void
{
    try {
        asyncAccessRequestConnection()->executeQuery('SELECT 1');
        asyncAccessRequestConnection()->executeQuery('SELECT COUNT(*) FROM trackers.access_requests');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function findAsyncAccessRequestId(string $email): ?string
{
    $id = asyncAccessRequestConnection()->fetchOne(
        'SELECT id FROM trackers.access_requests WHERE normalized_email = ?',
        [mb_strtolower($email)],
    );

    return is_string($id) ? $id : null;
}

function deleteAsyncAccessRequestData(): void
{
    try {
        asyncAccessRequestConnection()->executeStatement(
            'DELETE FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [ACCESS_REQUEST_ASYNC_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

function asyncAccessRequestTransport(): InMemoryTransport
{
    $transport = test()->getContainer()->get('messenger.transport.async');

    expect($transport)->toBeInstanceOf(InMemoryTransport::class);

    return $transport;
}

function asyncAccessRequestConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}
