<?php

declare(strict_types=1);

/*
 * Integration tests for public access request API Platform route exposure.
 * They verify the frontend-compatible path and operation metadata used by the public creation endpoint.
 */

use ApiPlatform\Metadata\ApiResource;
use App\AccessRequest\Api\Resource\AccessRequestResource;
use Symfony\Component\Routing\RouterInterface;

it('exposes the frontend-compatible public access request route', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('POST');

    expect($router->match('/api/access-requests')['_route'])->toContain('access_request_create');
});

it('keeps public access request creation groups separate', function (): void {
    $operation = accessRequestOperationsByName()['access_request_create'];

    expect($operation->getDenormalizationContext())->toBe(['groups' => ['access_request:create']])
        ->and($operation->getInputFormats())->toBe(['json' => ['application/json']])
        ->and($operation->getOutput())->toBeFalse();
});

/** @return array<string, \ApiPlatform\Metadata\Operation> */
function accessRequestOperationsByName(): array
{
    $attributes = (new ReflectionClass(AccessRequestResource::class))->getAttributes(ApiResource::class);
    $resource = $attributes[0]->newInstance();
    $operations = [];

    foreach ($resource->getOperations() as $operation) {
        $operations[(string) $operation->getName()] = $operation;
    }

    return $operations;
}
