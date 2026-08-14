<?php

declare(strict_types=1);

/*
 * Functional tests for the reference data HTTP endpoint.
 * They verify security behavior through the Symfony/API Platform stack.
 */

it('requires authentication for reference data', function (): void {
    $client = self::createClient();

    $client->request('GET', '/api/reference-data', server: ['HTTP_ACCEPT' => 'application/json']);

    expect($client->getResponse()->getStatusCode())->toBe(401)
        ->and($client->getResponse()->getContent())->toBe('{"message":"Authentication required.","details":[]}');
});