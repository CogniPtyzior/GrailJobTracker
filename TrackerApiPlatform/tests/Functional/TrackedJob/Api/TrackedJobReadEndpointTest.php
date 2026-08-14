<?php

declare(strict_types=1);

/*
 * Functional tests for tracked job read endpoints.
 * They verify security behavior through the Symfony/API Platform stack without relying on seeded database state.
 */

it('requires authentication for tracked job collection reads', function (): void {
    $client = self::createClient();

    $client->request('GET', '/api/tracked-jobs', server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($client->getResponse()->getContent())->toBe('{"message":"Authentication required.","details":[]}');
});

it('requires authentication for tracked job item reads', function (): void {
    $client = self::createClient();

    $client->request(
        'GET',
        '/api/tracked-jobs/018f6d6f-0000-7000-8000-000000000004',
        server: ['HTTP_ACCEPT' => 'application/json'],
    );

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($client->getResponse()->getContent())->toBe('{"message":"Authentication required.","details":[]}');
});

it('requires authentication for company suggestions', function (): void {
    $client = self::createClient();

    $client->request(
        'GET',
        '/api/tracked-jobs/company-suggestions?q=acme',
        server: ['HTTP_ACCEPT' => 'application/json'],
    );

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($client->getResponse()->getContent())->toBe('{"message":"Authentication required.","details":[]}');
});

it('requires authentication for CSV export', function (): void {
    $client = self::createClient();

    $client->request(
        'POST',
        '/api/tracked-jobs/export-csv',
        server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
        content: '{}',
    );

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($client->getResponse()->getContent())->toBe('{"message":"Authentication required.","details":[]}');
});
