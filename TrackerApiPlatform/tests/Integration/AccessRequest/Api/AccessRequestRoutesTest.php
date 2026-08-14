<?php

declare(strict_types=1);

/*
 * Integration tests for access request API Platform route exposure.
 * They verify frontend-compatible paths and separated serializer groups for public and admin operations.
 */

use ApiPlatform\Metadata\ApiResource;
use App\AccessRequest\Api\Resource\AccessRequestResource;
use Symfony\Component\Routing\RouterInterface;

it('exposes frontend-compatible public and admin access request routes', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);

    $router->getContext()->setMethod('GET');
    expect($router->match('/api/admin/access-requests')['_route'])->toContain('admin_access_request_list');

    $router->getContext()->setMethod('POST');
    expect($router->match('/api/access-requests')['_route'])->toContain('access_request_create')
        ->and($router->match('/api/admin/access-requests/018f6d6f-0000-7000-8000-000000000901/approve')['_route'])
        ->toContain('admin_access_request_approve');

    $router->getContext()->setMethod('DELETE');
    expect($router->match('/api/admin/access-requests/018f6d6f-0000-7000-8000-000000000901')['_route'])
        ->toContain('admin_access_request_delete');
});

it('keeps public and admin access request serializer groups separate', function (): void {
    $operations = accessRequestOperationsByName();

    expect($operations['access_request_create']->getDenormalizationContext())
        ->toBe(['groups' => ['access_request:create']])
        ->and($operations['access_request_create']->getInputFormats())->toBe(['json' => ['application/json']])
        ->and($operations['access_request_create']->getOutput())->toBeFalse()
        ->and($operations['admin_access_request_list']->getNormalizationContext())
        ->toBe(['groups' => ['access_request:list', 'access_request:read']])
        ->and($operations['admin_access_request_approve']->getDenormalizationContext())
        ->toBe(['groups' => ['access_request:approve']])
        ->and($operations['admin_access_request_approve']->getNormalizationContext())
        ->toBe(['groups' => ['access_request:approved']])
        ->and($operations['admin_access_request_delete']->getOutput())->toBeFalse();
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
