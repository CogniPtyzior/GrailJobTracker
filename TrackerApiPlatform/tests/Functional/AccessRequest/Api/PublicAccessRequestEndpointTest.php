<?php

declare(strict_types=1);

/*
 * Functional tests for the public access request endpoint.
 * They exercise API Platform validation, processor persistence and rate limiting through HTTP.
 */

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

const ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX = 'p19-public-';

afterEach(function (): void {
    if (self::$booted) {
        deletePublicAccessRequestData();
    }
});

it('creates a public access request through API Platform', function (): void {
    skipPublicAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensurePublicAccessRequestDatabaseAvailable();
    deletePublicAccessRequestData();

    submitPublicAccessRequest($client, ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'created@example.com');

    expect($client->getResponse()->getStatusCode())->toBe(201)
        ->and($client->getResponse()->getContent())->toBe('[]')
        ->and(countPublicAccessRequests())->toBe(1);
});

it('rejects invalid public access request payloads before persistence', function (): void {
    skipPublicAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensurePublicAccessRequestDatabaseAvailable();
    deletePublicAccessRequestData();

    submitPublicAccessRequest(
        $client,
        ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'invalid@example.com',
        companyName: '   ',
        reason: 'Too short',
    );

    expect($client->getResponse()->getStatusCode())->toBeGreaterThanOrEqual(400)
        ->and($client->getResponse()->getStatusCode())->toBeLessThan(500)
        ->and(countPublicAccessRequests())->toBe(0);
});

it('rate limits public access request submissions by remote address', function (): void {
    skipPublicAccessRequestIfPdoMissing();
    $client = self::createClient();
    ensurePublicAccessRequestDatabaseAvailable();
    deletePublicAccessRequestData();
    $remoteAddress = '203.0.113.'.random_int(1, 254);

    submitPublicAccessRequest($client, ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'allowed-1@example.com', remoteAddress: $remoteAddress);
    expect($client->getResponse()->getStatusCode())->toBe(201);

    submitPublicAccessRequest($client, ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'allowed-2@example.com', remoteAddress: $remoteAddress);
    expect($client->getResponse()->getStatusCode())->toBe(201)
        ->and(countPublicAccessRequests())->toBe(2);

    submitPublicAccessRequest($client, ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'blocked@example.com', remoteAddress: $remoteAddress);
    expect($client->getResponse()->getStatusCode())->toBe(429)
        ->and(countPublicAccessRequests())->toBe(2);
});

function submitPublicAccessRequest(
    KernelBrowser $client,
    string $email,
    string $companyName = 'Acme',
    string $reason = 'I would like to try the tracker for my job search.',
    ?string $remoteAddress = null,
): void {
    $client->request(
        'POST',
        '/api/access-requests',
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => $remoteAddress ?? '198.51.100.'.random_int(1, 254),
        ],
        content: json_encode([
            'email' => $email,
            'companyName' => $companyName,
            'reason' => $reason,
        ], JSON_THROW_ON_ERROR),
    );
}

function skipPublicAccessRequestIfPdoMissing(): void
{
    if (!extension_loaded('pdo_pgsql')) {
        test()->markTestSkipped('pdo_pgsql is required for access request endpoint functional tests.');
    }
}

function ensurePublicAccessRequestDatabaseAvailable(): void
{
    try {
        publicAccessRequestConnection()->executeQuery('SELECT 1');
        publicAccessRequestConnection()->executeQuery('SELECT COUNT(*) FROM trackers.access_requests');
    } catch (Throwable $exception) {
        test()->markTestSkipped('PostgreSQL test database is not available: '.$exception->getMessage());
    }
}

function countPublicAccessRequests(): int
{
    return (int) publicAccessRequestConnection()->fetchOne(
        'SELECT COUNT(*) FROM trackers.access_requests WHERE normalized_email LIKE ?',
        [ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'%'],
    );
}

function deletePublicAccessRequestData(): void
{
    try {
        publicAccessRequestConnection()->executeStatement(
            'DELETE FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [ACCESS_REQUEST_PUBLIC_EMAIL_PREFIX.'%'],
        );
    } catch (Throwable) {
    }
}

function publicAccessRequestConnection(): Connection
{
    return test()->getContainer()->get(Connection::class);
}
